<?php

namespace Clang\Clang\Model;

use \Magento\Framework\Model\AbstractModel;
use \Clang\Clang\Api\ConnectionResponseInterface;

class ConnectionResponse extends AbstractModel implements ConnectionResponseInterface
{
    const KEY_VERSION = 'version';
    const KEY_MAGENTOVERSION = 'magento_version';
    const KEY_RESPONSES = 'responses';

    /**
     * {@inheritdoc}
     */
    public function getMagentoVersion()
    {
        return $this->_getData(self::KEY_MAGENTOVERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoVersion($message)
    {
        return $this->setData(self::KEY_MAGENTOVERSION, $message);
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

    /**
     * Connection responses
     * @return mixed[]
     */

    public function getConnectionResponse()
    {
        return $this->_getData(self::KEY_RESPONSES);
    }

    /**
     * Set the Connection responses
     * @param array $response
     * @return mixed[]
     */

    public function setConnectionResponse($response)
    {
        return $this->setData(self::KEY_RESPONSES, $response);
    }
}
