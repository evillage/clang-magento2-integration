<?php


namespace Clang\Clang\Api;

interface ManagementApiInterface
{

    /**
     * Ping clang
     * @return string
     */

    public function ping();

    /**
     * GET info about extension
     * @return Clang\Clang\Api\InfoResponseInterface
     */

    public function getInfo();

    /**
     * Get product url by sku
     * @param string $sku
     * @param integer $storeId
     * @return string
     */

    public function getProductUrlBySku($sku, $storeId);

    /**
     * Setup the extension
     * @param Clang\Clang\Api\SetupSettingsInterface[] $settings
     * @return boolean
     */

    public function setup($settings);

    /**
     * Enable / disable mails
     * @param Clang\Clang\Api\MailSettingInterface[] $mailSettings
     * @return boolean
     */

    public function disableMails($mailSettings);

    /**
     * Get unsubscribe url
     * @param string $emailaddress
     * @param integer $storeId
     * @return string
     */
    public function getUnsubscribeUrl($emailaddress, $storeId);

    /**
     * Check enabled / disabled mails
     * @return Clang\Clang\Api\MailSettingInterface[]
     */
    public function checkMails();

    /**
     * Check the setup
     * @return Clang\Clang\Api\SetupSettingsInterface[]
     */

    public function check_setup();

    /**
     * Get the call log
     * @param string $filter
     * @param integer $page
     * @param integer $pageSize
     * @return Clang\Clang\Model\CallLogInterface[]
     */

    public function get_log($filter, $page = false, $pageSize = false);
}
