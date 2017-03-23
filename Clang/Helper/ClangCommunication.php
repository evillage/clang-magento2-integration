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

    public function postNewOrder($storeId, $orderData){
        if($this->isStoreConnected($storeId)){
            $this->logger->info('postNewOrder');
            $this->clangApi->postNewOrder($storeId, $orderData);
        }
    }

    public function postData($storeId, $endpoint, $data){
        if($this->isStoreConnected($storeId)){
            $this->logger->info('postData');
            $this->clangApi->postData($storeId, $endpoint, $data);
        }
    }
}