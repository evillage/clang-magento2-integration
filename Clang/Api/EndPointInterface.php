<?php


namespace Clang\Clang\Api;

interface EndPointInterface
{

    /**
     * Endpoint type
     * @return string
     */

    public function getType();

    /**
     * Get the endpoint
     * @return string
     */

    public function getEndPoint();

    /**
     * Set endpoint type
     * @param string $type
     * @return mixed
     */

    public function setType($clang_token);

    /**
     * Set the endpoint
     * @param string $endpoint
     * @return mixed
     */

    public function setEndPoint($endpoint);
}
