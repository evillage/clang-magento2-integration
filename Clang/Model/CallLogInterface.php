<?php
namespace Clang\Clang\Model;
interface CallLogInterface
{
    /**
     * @return integer
     */
    public function getId();

    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @return string
     */
    public function getRequestData();

    /**
     * @return string
     */
    public function getResponse();

    /**
     * @return string
     */
    public function getResponseCode();

    /**
     * @return integer
     */
    public function getStoreId();

    /**
     * @return string
     */
    public function getCallTime();
}