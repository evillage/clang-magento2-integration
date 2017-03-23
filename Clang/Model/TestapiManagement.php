<?php


namespace Clang\Clang\Model;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Cache\Frontend\Pool;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\ScopeInterface;

class TestapiManagement
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
    protected $logger;
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

        $this->logger = $logger;
        $this->logger->info(get_class($configReader));
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
        $info->setMessage('Hello, this is the Magento <> Clang integration, who are you? Please tell me by executing my setup-call!');
        $info->setVersion('1.0.0');
        return $info;
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
    public function getTestapi($param)
    {
        $param = $this->configReader->getValue('clang/clang/testsetting', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        return 'hello api GET return the $param ' . $param;
    }
    /**
     * {@inheritdoc}
     */
    public function postTestapi($param)
    {
        $this->configWriter->save('clang/clang/testsetting', 'HALLO SIMON: '.$param, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        $types = array('config');
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return 'hello api POST return the $param ' . $param;
    }
}