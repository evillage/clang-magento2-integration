<?php


namespace Clang\Clang\Api;

interface ManagementApiInterface
{

    /**
     * Returns the magento version and the extension version. Used by Clang to
     * determine the extension is setup and updated to the latest version before
     * connecting with it.
     *
     * @return Clang\Clang\Api\InfoResponseInterface
     */
    public function getInfo();

    /**
     * Test the API connection to Clang. Used by Clang to verify if the setup
     * is completely done and the connection is working.
     *
     * @return Clang\Clang\Api\ConnectionResponseInterface
     */
    public function testConnection();

    /**
     * Gets a complete product url by sku and storeId
     *
     * @param string $sku
     * @param integer $storeId
     * @return string
     */
    public function getProductUrlBySku($sku, $storeId);

    /**
     * Setup the extension. Used once when a connection is made from Clang to
     * magento. Clang will provide the extension with the correct callback URLs
     * to succesfully connect back to Clang.
     *
     * @param Clang\Clang\Api\SetupSettingsInterface[] $settings
     * @return boolean
     */
    public function setup($settings);

    /**
     * Enable / disable mails. This is used when there is a mail campaign
     * setup in Clang to tell the extension to stop sending messages for this
     * campaign using magento (because Clang is sending the messages already).
     *
     * @param Clang\Clang\Api\MailSettingInterface[] $mailSettings
     * @return boolean
     */
    public function disableMails($mailSettings);

    /**
     * Get unsubscribe url for a customer. Needed because a lot of countries require
     * working unsubscribe url's in all mail messages.
     *
     * @param string $emailaddress
     * @param integer $storeId
     * @return string
     */
    public function getUnsubscribeUrl($emailaddress, $storeId);

    /**
     * Check enabled / disabled mails. Used by Clang to verify which messages are
     * send by Magento and which are not, to make sure Clang doesn't send the same
     * messages.
     *
     * @return Clang\Clang\Api\MailSettingInterface[]
     */
    public function checkMails();

    /**
     * Check the setup. Retrieves the setup settings. Used by Clang to verify the
     * setup call was sucessfull.
     *
     * @return Clang\Clang\Api\SetupSettingsInterface[]
     */
    public function checkSetup();

    /**
     * Retrieves the call log, used by Clang to retrieve any calls which were made
     * by the Magento extension but were unsuccesfull. In this case Clang is able
     * to process the data in the logs to make sure no data and customer communication
     * get lost.
     *
     * @param string $filter
     * @param integer $page
     * @param integer $pageSize
     * @return Clang\Clang\Model\CallLogInterface[]
     */
    public function getLog($filter, $page = false, $pageSize = false);
}
