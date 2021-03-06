<?php

namespace Clang\Clang\Model;

class InfoResponse extends \Magento\Framework\Model\AbstractModel implements \Clang\Clang\Api\InfoResponseInterface
{
    const KEY_VERSION = 'version';
    const KEY_MAGENTOVERSION = 'magento_version';

    /**
     * {@inheritdoc}
     */
    public function getMagentoVersion()
    {
        return $this->_getData(self::KEY_MAGENTOVERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoVersion($message)
    {
        return $this->setData(self::KEY_MAGENTOVERSION, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->_getData(self::KEY_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        return $this->setData(self::KEY_VERSION, $version);
    }
}
