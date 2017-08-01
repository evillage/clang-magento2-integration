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
use \Magento\Store\Model\ScopeInterface;

class ClangCommunication extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $configReader;
    protected $storeManager;
    protected $moduleList;
    protected $productMetadata;
    protected $clangApi;

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

        $this->configReader    = $context->getScopeConfig();
        $this->storeManager    = $storeManager;
        $this->moduleList      = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->clangApi        = $clangApi;
    }

    public function isStoreConnected($storeId)
    {
        $token = $this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId);
        return !empty($token);
    }

    public function postData($storeId, $endpoint, $data)
    {
        if ($this->isStoreConnected($storeId)) {
            $this->clangApi->postData($storeId, $endpoint, $data);
        }
    }
}
