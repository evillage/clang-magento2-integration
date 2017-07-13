<?php
namespace Clang\Clang\Model\ResourceModel;

class CallLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('clang_clang_calllog', 'clang_clang_calllog_id');
    }
}
