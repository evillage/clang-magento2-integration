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
    protected $connectionResponseFactory;
    protected $setupSettingsFactory;
    protected $endPointFactory;
    protected $searchCriteriaBuilder;
    protected $callLogRepository;
    protected $storeManager;
    protected $productRepository;
    protected $clangApi;
    protected $mailSettingFactory;
    protected $subscriber;
    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $configReader,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        \Clang\Clang\Api\InfoResponseInterfaceFactory $infoResponseFactory,
        \Clang\Clang\Api\ConnectionResponseInterfaceFactory $connectionResponseFactory,
        \Clang\Clang\Api\SetupSettingsInterfaceFactory $setupSettingsFactory,
        \Clang\Clang\Api\EndPointInterfaceFactory $endPointFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CallLogRepository $callLogRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Clang\Clang\Api\MailSettingInterfaceFactory $mailSettingFactory,
        \Clang\Clang\Helper\ClangApi $clangApi,
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $this->configWriter              = $configWriter;
        $this->configReader              = $configReader;
        $this->cacheTypeList             = $cacheTypeList;
        $this->cacheFrontendPool         = $cacheFrontendPool;
        $this->infoResponseFactory       = $infoResponseFactory;
        $this->connectionResponseFactory = $connectionResponseFactory;
        $this->setupSettingsFactory      = $setupSettingsFactory;
        $this->endPointFactory           = $endPointFactory;
        $this->searchCriteriaBuilder     = $searchCriteriaBuilder;
        $this->callLogRepository         = $callLogRepository;
        $this->storeManager              = $storeManager;
        $this->productRepository         = $productRepository;
        $this->mailSettingFactory        = $mailSettingFactory;
        $this->clangApi                  = $clangApi;
        $this->subscriber                = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUrlBySku($sku, $storeId)
    {
        // Find the product and return the product URL if succesful.  If product is not found an empty
        // string is returned. Clang will handle this situation.
        $product = $this->productRepository->get($sku, false, $storeId);
        if ($product) {
            return $product->getUrlModel()->getUrl($product);
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        // Clang needs to know it is communicating with an updated Magento and extension. So return
        // this information when needed.

        $info = $this->infoResponseFactory->create();
        $info->setMagentoVersion($this->clangApi->getMagentoVersion());
        $info->setVersion($this->clangApi->getExtensionVersion());
        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection()
    {
        // After Clang sets up the connection using the setup call it asks the extension to do
        // some status calls to check for a working connection to Clang.
        $responses = $this->clangApi->postStatus();

        $info = $this->connectionResponseFactory->create();
        $info->setMagentoVersion($this->clangApi->getMagentoVersion());
        $info->setVersion($this->clangApi->getExtensionVersion());
        $info->setResponses($responses);
        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function setup($settings)
    {
        // This call is used once when a connection is made from Clang to magento. Clang will provide
        // the extension with the correct callback URLs. We store these urls in the config here. This
        // might also be called if there are any URL changes in Clang.
        foreach ($settings as $storeSettings) {
            $storeId = $storeSettings->getStoreId();

            $this->configWriter->save(
                'clang/clang/clang_token',
                $storeSettings->getClangToken(),
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            foreach ($storeSettings->getEndPoints() as $endpoint) {
                $this->configWriter->save(
                    'clang/clang/endpoint/'.$endpoint->getType(),
                    $endpoint->getEndPoint(),
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
            }
        }

        // To create a succesfull and safe connection we need to clean up the cache immediately, otherwise
        // there is a risk of sending data to cached URL's wich might prove to be a security risk if they
        // are changed. Normally this call will only be executed during setup of the connection in Clang.
        $this->cacheTypeList->cleanType('config');
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkSetup()
    {
        // Retrieve the set up endpoint URL's which were saved in the setup function. Clang will be able to
        // check if these URL's are correct.
        $settings = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            $endpoints = [];

            foreach (['status', 'generic'] as $type) {
                $endpoint = $this->endPointFactory->create();
                $endpoint->setType($type);
                $endpoint->setEndPoint($this->configReader->getValue(
                    'clang/clang/endpoint/'.$type,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                ));

                $endpoints[] = $endpoint;
            }

            $token = $this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId);

            $setup = $this->setupSettingsFactory->create();
            $setup->setClangToken($token);
            $setup->setEndPoints($endpoints);
            $setup->setStoreId($storeId);

            $settings[] = $setup;
        }
        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getLog($filter, $page = false, $pageSize = false)
    {
        // Retrieve the call log using some filters. This is used by Clang to retrieve any
        // calls which were made by magento but unsuccesfully processed by Clang. Clang
        // will be able to reprocess the data from the log to make sure no data or customer
        // communication will be lost.
        $builder = $this->searchCriteriaBuilder
            ->setPageSize($pageSize ?: 25)
            ->setCurrentPage($page ?: 1);

        $filter = json_decode($filter, true);

        $allowedFields = ['id', 'store_id', 'endpoint', 'data', 'response_code', 'response', 'call_time'];
        $allowedOps = ['eq', 'lt', 'gt'];

        foreach ($filter as $f) {
            if (in_array($f['field'], $allowedFields) &&
                in_array($f['operation'], $allowedOps)) {
                switch ($f['field']) {
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
        if ($total > ($page-1)*$pageSize) {
            return $callLog->getItems();
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function disableMails($mailSettings)
    {

        // Enable / disable mails. This is used when there is a mail campaign
        // setup in Clang to tell the extension to stop sending messages for this
        // campaign using magento (because Clang is sending the messages already).
        $mailNames = [];
        foreach ($mailSettings as $mailSetting) {
            $storeId = $mailSetting->getStoreId();

            $this->configWriter->save(
                'clang/clang/disable_mail/'.$mailSetting->getMailName(),
                $mailSetting->getDisabled(),
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            $mailNames[] = $mailSetting->getMailName();
        }
        $this->configWriter->save('clang/clang/disable_mailnames', implode(',', $mailNames));

        // We need to clean up the cache immediately, otherwise there is a high risk of sending duplicate
        // communication to customers because both Clang and Magento will be sending messages then.
        $this->cacheTypeList->cleanType('config');
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkMails()
    {
        // Check enabled / disabled mails. Used by Clang to verify which messages are
        // send by Magento and which are not, to make sure Clang doesn't send the same
        // messages.
        $settings = [];
        $mailNames = array_filter(explode(',', $this->configReader->getValue('clang/clang/disable_mailnames')));

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();

            foreach ($mailNames as $type) {
                $disabled = $this->configReader->getValue(
                    'clang/clang/disable_mail/'.$type,
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );

                $mailSetting = $this->mailSettingFactory->create();
                $mailSetting->setStoreId($storeId);
                $mailSetting->setMailName($type);
                $mailSetting->setDisabled($disabled);

                $settings[] = $mailSetting;
            }
        }
        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnsubscribeUrl($emailaddress, $storeId)
    {
        // Get unsubscribe url for a customer. Needed because a lot of countries require
        // working unsubscribe url's in all mail messages.
        $connection = $this->subscriber->getResource()->getConnection();

        $select = $connection
            ->select()
            ->from($this->subscriber->getResource()->getMainTable())
            ->where('subscriber_email=:subscriber_email and store_id=:store_id');

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
