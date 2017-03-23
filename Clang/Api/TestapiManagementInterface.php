<?php


namespace Clang\Clang\Api;

interface TestapiManagementInterface
{


    /**
     * GET for testapi api
     * @param string $param
     * @return string
     */

    public function getTestapi($param);

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
     * POST for testapi api
     * @param string $param
     * @return string
     */

    public function postTestapi($param);

    /**
     * Setup the extension
     * @param Clang\Clang\Api\SetupSettingsInterface[] $settings
     * @return boolean
     */

    public function setup($settings);

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