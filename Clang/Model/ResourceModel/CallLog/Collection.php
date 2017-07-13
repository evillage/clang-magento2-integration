<?php
namespace Clang\Clang\Model\ResourceModel\CallLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Clang\Clang\Model\CallLog', 'Clang\Clang\Model\ResourceModel\CallLog');
    }
}
