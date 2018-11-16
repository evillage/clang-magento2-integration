<?php

namespace Clang\Clang\Api;

interface ConnectionResponseInterface
{

    /**
     * Version number of the extension
     * @return string
     */

    public function getVersion();

    /**
     * Version number of magento
     * @return string
     */

    public function getMagentoVersion();

    /**
     * Set the version number of the extension
     * @param string $version
     * @return string
     */

    public function setVersion($version);

    /**
     * Set the version number of magento
     * @param string $message
     * @return string
     */

    public function setMagentoVersion($version);

    /**
     * Connection responses
     * @return mixed[]
     */
    public function getConnectionResponse();

    /**
     * Set the Connection responses
     * @param array $response
     * @return mixed[]
     */
    public function setConnectionResponse($response);
}
