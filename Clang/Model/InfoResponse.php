<?php

namespace Clang\Clang\Model;

class InfoResponse extends \Magento\Framework\Model\AbstractModel implements \Clang\Clang\Api\InfoResponseInterface
{
    const KEY_VERSION = 'version';
    const KEY_MESSAGE = 'message';


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->_getData(self::KEY_MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        return $this->setData(self::KEY_MESSAGE, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->_getData(self::KEY_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        return $this->setData(self::KEY_VERSION, $version);
    }


}