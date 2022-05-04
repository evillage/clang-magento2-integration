<?php

namespace Clang\Clang;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const ATTRIBUTE_MAPPING_MODE = 'clang/clang/product_attribute_mapping_mode';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getAttributeMappingMode ($storeCode = null) {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_MAPPING_MODE, ScopeInterface::SCOPE_STORE, $storeCode);
    }
}
