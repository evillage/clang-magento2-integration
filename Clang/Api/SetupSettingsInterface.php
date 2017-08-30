<?php


namespace Clang\Clang\Api;

interface SetupSettingsInterface
{

    /**
     * StoreId
     * @return integer
     */

    public function getStoreId();

    /**
     * Clang Token
     * @return string
     */

    public function getClangToken();

    /**
     * Clang App Id
     * @return string
     */

    public function getClangAppId();

    /**
     * Get the api endpoints
     * @return Clang\Clang\Api\EndPointInterface[]
     */

    public function getEndPoints();

    /**
     * Set the store id
     * @param string $store_id
     * @return mixed
     */

    public function setStoreId($store_id);

    /**
     * Set the Clang Token
     * @param string $clang_token
     * @return mixed
     */

    public function setClangToken($clang_token);

    /**
     * Set the Clang App Id
     * @param string $clang_app_id
     * @return mixed
     */

    public function setClangAppId($clang_app_id);

    /**
     * Set the api endpoints
     * @param Clang\Clang\Api\EndPointInterface[] $endpoints
     * @return mixed
     */

    public function setEndPoints($endpoints);
}
