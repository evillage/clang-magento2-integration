<?php

namespace Clang\Clang\Api;

interface InfoResponseInterface
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
}
