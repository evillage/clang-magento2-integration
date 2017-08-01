<?php

namespace Clang\Clang\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerUpdate implements ObserverInterface
{
    protected $clangCommunication;
    protected $clangDataHelper;
    protected $customerRegistry;

    public function __construct(
        \Clang\Clang\Helper\ClangCommunication $clangCommunication,
        \Clang\Clang\Helper\Data $clangDataHelper,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry
    ) {
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper = $clangDataHelper;
        $this->customerRegistry = $customerRegistry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = false;
        $customerId = false;

        $customer = $observer->getEvent()->getCustomerDataObject();
        if ($customer instanceof \Magento\Customer\Model\Data\Customer) {
            $storeId = $customer->getStoreId();
            $customerId = $customer->getId();
        } else {
            $customerAddress = $observer->getEvent()->getCustomerAddress();
            if ($customerAddress instanceof \Magento\Customer\Model\Address) {
                $customerId = $customerAddress->getCustomerId();
            }
        }

        if ($customerId) {
            $customer = $this->customerRegistry->retrieve($customerId);
            if ($customer instanceof \Magento\Framework\Model\AbstractModel) {
                if (!$storeId) {
                    $storeId = $customer->getStoreId();
                }

                $objects = [];
                $data = $this->clangDataHelper->toArray($customer->getDataModel(), $objects);

                $this->clangCommunication->postData($storeId, 'update-customer', ['customer'=>$data]);
            }
        }
        return $this;
    }
}
