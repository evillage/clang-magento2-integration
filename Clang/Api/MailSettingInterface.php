<?php


namespace Clang\Clang\Api;

interface MailSettingInterface
{

    /**
     * StoreId
     * @return integer
     */

    public function getStoreId();

    /**
     * Mail name
     * @return string
     */

    public function getMailName();

    /**
     * Disabled
     * @return boolean
     */
    public function getDisabled();

    /**
     * Set the store id
     * @param integer $store_id
     * @return mixed
     */

    public function setStoreId($store_id);

    /**
     * Set the Mail name
     * @param string $mail_name
     * @return mixed
     */

    public function setMailName($mail_name);

    /**
     * Set the disabled state
     * @param boolean $disabled
     * @return mixed
     */

    public function setDisabled($disabled);

}