<?php
namespace Clang\Clang\Model;

class CallLog extends \Magento\Framework\Model\AbstractModel implements CallLogInterface, \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'clang_clang_calllog';

    protected function _construct()
    {
        $this->_init('Clang\Clang\Model\ResourceModel\CallLog');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData('clang_clang_calllog_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint()
    {
        return $this->getData('endpoint');
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestData()
    {
        return $this->getData('data');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->getData('response');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseCode()
    {
        return $this->getData('response_code');
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getCallTime()
    {
        return $this->getData('call_time');
    }
}
