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
     * A welcome message from the extension
     * @return string
     */

    public function getMessage();

    /**
     * Set the version number of the extension
     * @param string $version
     * @return string
     */

    public function setVersion($version);

    /**
     * Set the welcome message from the extension
     * @param string $message
     * @return string
     */

    public function setMessage($message);

}