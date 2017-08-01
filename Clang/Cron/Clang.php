<?php
namespace Clang\Clang\Cron;

use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Clang\Clang\Model\CallLogRepository;

class Clang
{
    protected $searchCriteriaBuilder;
    protected $callLogRepository;
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CallLogRepository $callLogRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->callLogRepository     = $callLogRepository;
    }

    public function execute()
    {
        $pageSize = 25;
        $page     = 0;
        $offset   = 30*24*3600;

        do {
            $searchCriteria = $this->searchCriteriaBuilder
                ->setPageSize($pageSize)
                ->setCurrentPage(++$page)
                ->addFilter('creation_time', date('Y-m-d H:i:s', time()-$offset), 'lt')
                ->create();

            $callLog = $this->callLogRepository->getList($searchCriteria);
            $total = $callLog->getTotalCount();
            foreach ($callLog->getItems() as $callLogItem) {
                $callLogItem->delete();
            }
        } while (ceil($total/$pageSize)>$page);
    }
}
