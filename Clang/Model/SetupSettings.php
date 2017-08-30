<?php

namespace Clang\Clang\Model;

class SetupSettings extends \Magento\Framework\Model\AbstractModel implements \Clang\Clang\Api\SetupSettingsInterface
{
    const KEY_STOREID    = 'store_id';
    const KEY_CLANGTOKEN = 'clang_token';
    const KEY_CLANGAPPID = 'clang_app_id';
    const KEY_ENDPOINTS  = 'end_points';

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return $this->_getData(self::KEY_STOREID);
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($store_id)
    {
        return $this->setData(self::KEY_STOREID, $store_id);
    }

    /**
     * {@inheritdoc}
     */
    public function getClangToken()
    {
        return $this->_getData(self::KEY_CLANGTOKEN);
    }

    /**
     * {@inheritdoc}
     */
    public function setClangToken($clang_token)
    {
        return $this->setData(self::KEY_CLANGTOKEN, $clang_token);
    }

    /**
     * {@inheritdoc}
     */
    public function getClangAppId()
    {
        return $this->_getData(self::KEY_CLANGAPPID);
    }

    /**
     * {@inheritdoc}
     */
    public function setClangAppId($clang_app_id)
    {
        return $this->setData(self::KEY_CLANGAPPID, $clang_app_id);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndPoints()
    {
        return $this->_getData(self::KEY_ENDPOINTS);
    }

    /**
     * {@inheritdoc}
     */
    public function setEndPoints($endpoints)
    {
        return $this->setData(self::KEY_ENDPOINTS, $endpoints);
    }
}
