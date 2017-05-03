<?php
/**
 * Copyright © 2015 Clang . All rights reserved.
 */
namespace Clang\Clang\Helper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Module\ModuleListInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Helper\Context as HelperContext;
use \Magento\Framework\App\ProductMetadataInterface;
use \Psr\Log\LoggerInterface;
use \Magento\Store\Model\ScopeInterface;

class ClangCommunication extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $configReader;
    protected $storeManager;
    protected $moduleList;
    protected $productMetadata;
    protected $clangApi;
    protected $logger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HelperContext $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        ClangApi $clangApi
    ) {
        parent::__construct($context);

        $this->configReader         = $context->getScopeConfig();
        $this->storeManager         = $storeManager;
        $this->moduleList           = $moduleList;
        $this->productMetadata      = $productMetadata;
        $this->clangApi             = $clangApi;

        $this->logger = $context->getLogger();
        $this->logger->info('TEST');
    }

    public function isStoreConnected($storeId){
        $token = $this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId);
        return !empty($token);
    }

    protected $queuedData = [];

    public function queueData($storeId, $endpoint, $data, $id = null){
            $this->logger->info(__METHOD__);
        if($this->isStoreConnected($storeId)){
            if(is_null($id)){
                $id = md5($storeId.$endpoint.json_encode($data));
            }
            $this->queuedData[$id] = [$storeId, $endpoint, $data];
        }
    }

    public function postQueue(){
            $this->logger->info(__METHOD__);
        foreach($this->queuedData as $data){
            $this->postData($data[0], $data[1], $data[2]);
        }
        $this->queuedData = [];
    }

    public function clearQueue($id = null){
            $this->logger->info(__METHOD__);
        if(is_null($id)){
            $this->queuedData = [];
        }
        else{
            unset($this->queuedData[$id]);
        }
    }

    public function postData($storeId, $endpoint, $data){
        if($this->isStoreConnected($storeId)){
            $this->logger->info('postData');
            $this->clangApi->postData($storeId, $endpoint, $data);
        }
    }
}