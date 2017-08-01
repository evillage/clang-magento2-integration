<?php

namespace Clang\Clang\Model;

class MailSetting extends \Magento\Framework\Model\AbstractModel implements \Clang\Clang\Api\MailSettingInterface
{
    const KEY_STOREID    = 'store_id';
    const KEY_MAILNAME   = 'mail_name';
    const KEY_DISABLED   = 'disabled';

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
    public function getMailName()
    {
        return $this->_getData(self::KEY_MAILNAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setMailName($mail_name)
    {
        return $this->setData(self::KEY_MAILNAME, $mail_name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisabled()
    {
        return (boolean)$this->_getData(self::KEY_DISABLED);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisabled($disabled)
    {
        return $this->setData(self::KEY_DISABLED, (boolean)$disabled);
    }
}
