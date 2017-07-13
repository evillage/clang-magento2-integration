<?php


namespace Clang\Clang\Model;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Cache\Frontend\Pool;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\ScopeInterface;

class ManagementApi
{


    protected $configWriter;
    protected $configReader;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $infoResponseFactory;
    protected $setupSettingsFactory;
    protected $endPointFactory;
    protected $searchCriteriaBuilder;
    protected $callLogRepository;
    protected $storeManager;
    protected $productRepository;
    protected $clangApi;
    protected $mailSettingFactory;
    protected $logger;
    protected $subscriber;
    public function __construct(
        WriterInterface                                $configWriter,
        ScopeConfigInterface                           $configReader,
        TypeListInterface                              $cacheTypeList,
        Pool                                           $cacheFrontendPool,
        \Clang\Clang\Api\InfoResponseInterfaceFactory  $infoResponseFactory,
        \Clang\Clang\Api\SetupSettingsInterfaceFactory $setupSettingsFactory,
        \Clang\Clang\Api\EndPointInterfaceFactory      $endPointFactory,
        SearchCriteriaBuilder                          $searchCriteriaBuilder,
        CallLogRepository                              $callLogRepository,
        \Magento\Store\Model\StoreManagerInterface     $storeManager,
        \Magento\Catalog\Model\ProductRepository       $productRepository,
        \Clang\Clang\Api\MailSettingInterfaceFactory      $mailSettingFactory,
        \Clang\Clang\Helper\ClangApi $clangApi,
        \Magento\Newsletter\Model\Subscriber $subscriber,

        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resource
    )
    {
        $this->configWriter          = $configWriter;
        $this->configReader          = $configReader;
        $this->cacheTypeList         = $cacheTypeList;
        $this->cacheFrontendPool     = $cacheFrontendPool;
        $this->infoResponseFactory   = $infoResponseFactory;
        $this->setupSettingsFactory  = $setupSettingsFactory;
        $this->endPointFactory       = $endPointFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->callLogRepository     = $callLogRepository;
        $this->storeManager          = $storeManager;
        $this->productRepository     = $productRepository;
        $this->mailSettingFactory    = $mailSettingFactory;
        $this->clangApi              = $clangApi;
        $this->subscriber            = $subscriber;

        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUrlBySku($sku, $storeId){
        $product = $this->productRepository->get($sku, false, $storeId);
        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        $info = $this->infoResponseFactory->create();
        $info->setMagentoVersion($this->clangApi->getMagentoVersion());
        $info->setVersion($this->clangApi->getExtensionVersion());
        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return json_encode($this->clangApi->pingClang());
    }

    /**
     * {@inheritdoc}
     */
    public function setup($settings)
    {
        foreach($settings as $storeSettings){
            $storeId = $storeSettings->getStoreId();

            $this->configWriter->save('clang/clang/clang_token', $storeSettings->getClangToken(), ScopeInterface::SCOPE_STORES, $storeId);

            foreach($storeSettings->getEndPoints() as $endpoint){
                $this->configWriter->save('clang/clang/endpoint/'.$endpoint->getType(), $endpoint->getEndPoint(), ScopeInterface::SCOPE_STORES, $storeId);
            }
        }

        $types = array('config');
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function check_setup()
    {
        $settings = [];
        foreach($this->storeManager->getStores() as $store){
            $storeId = $store->getId();
            $endpoints = [];

            foreach(['cron-status', 'new-order', 'generic'] as $type){
                $endpoint = $this->endPointFactory->create();
                $endpoint->setType($type);
                $endpoint->setEndPoint($this->configReader->getValue('clang/clang/endpoint/'.$type, ScopeInterface::SCOPE_STORES, $storeId));

                $endpoints[] = $endpoint;
            }

            $setup = $this->setupSettingsFactory->create();
            $setup->setClangToken($this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId));
            $setup->setEndPoints($endpoints);
            $setup->setStoreId($storeId);

            $settings[] = $setup;
        }
        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function get_log($filter, $page = false, $pageSize = false){
        $builder = $this->searchCriteriaBuilder
            ->setPageSize($pageSize ?: 25)
            ->setCurrentPage($page ?: 1);

        $filter = json_decode($filter, true);

        foreach($filter as $f){
            if(
                in_array($f['field'],     ['id', 'store_id', 'endpoint', 'data', 'response_code', 'response', 'call_time']) &&
                in_array($f['operation'], ['eq', 'lt', 'gt'])
            ){
                switch($f['field']){
                    case 'id':
                        $builder->addFilter('clang_clang_calllog_id', $f['value'], $f['operation']);
                        break;
                    default:
                        $builder->addFilter($f['field'], $f['value'], $f['operation']);
                        break;
                }
            }
        }

        $searchCriteria = $builder->create();

        $callLog = $this->callLogRepository->getList($searchCriteria);
        $total = $callLog->getTotalCount();

        header('X-Count: '.$total);
        header('X-Page-Count: '.ceil($total/$pageSize));
        if($total > ($page-1)*$pageSize){
            return $callLog->getItems();
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function disableMails($mailSettings){
        $mailNames = [];
        foreach($mailSettings as $mailSetting){

            $storeId = $mailSetting->getStoreId();

            $this->configWriter->save('clang/clang/disable_mail/'.$mailSetting->getMailName(), $mailSetting->getDisabled(), ScopeInterface::SCOPE_STORES, $storeId);

            $mailNames[] = $mailSetting->getMailName();
        }
        $this->configWriter->save('clang/clang/disable_mailnames', implode(',',$mailNames));

        $types = array('config');
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkMails(){
        $settings = [];
        $mailNames = array_filter(explode(',',$this->configReader->getValue('clang/clang/disable_mailnames')));

        foreach($this->storeManager->getStores() as $store){
            $storeId = $store->getId();

            foreach($mailNames as $type){
                $mailSetting = $this->mailSettingFactory->create();
                $mailSetting->setStoreId($storeId);
                $mailSetting->setMailName($type);
                $mailSetting->setDisabled($this->configReader->getValue('clang/clang/disable_mail/'.$type, ScopeInterface::SCOPE_STORES, $storeId));

                $settings[] = $mailSetting;
            }
        }
        return $settings;

    }

    /**
     * {@inheritdoc}
     */
    public function getUnsubscribeUrl($emailaddress, $storeId){
        $connection = $this->subscriber->getResource()->getConnection();

        $select = $connection->select()->from($this->subscriber->getResource()->getMainTable())->where('subscriber_email=:subscriber_email and store_id=:store_id');

        $result = $connection->fetchRow($select, [
            'subscriber_email' => $emailaddress,
            'store_id'         => $storeId
        ]);

        if (!$result) {
            return '';
        }

        return $this->subscriber->addData($result)->getUnsubscriptionLink();

    }
}