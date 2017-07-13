<?php
namespace Clang\Clang\Api;

use Clang\Clang\Model\CallLogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CallLogRepositoryInterface
{
    public function save(CallLogInterface $page);

    public function getById($id);

    public function getList(SearchCriteriaInterface $criteria);

    public function delete(CallLogInterface $page);

    public function deleteById($id);
}
