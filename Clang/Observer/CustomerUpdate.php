<?php

namespace Clang\Clang\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerUpdate implements ObserverInterface {
    protected $logger;
    protected $clangCommunication;
    protected $clangDataHelper;
    protected $customerRegistry;

    public function __construct(
        \Clang\Clang\Helper\ClangCommunication $clangCommunication,
        \Clang\Clang\Helper\Data $clangDataHelper,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper = $clangDataHelper;
        $this->customerRegistry = $customerRegistry;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        //TODO: Dit is niet meer nodig. Want we doen t via de transport.

        $this->logger->info('HALLO SIMON');
        $this->logger->debug('HALLO SIMON');
        $this->logger->error('HALLO SIMON');
        $this->logger->error('observer class: '.get_class($observer->getEvent()));
        $this->logger->error('observer class: '.print_r(array_keys($observer->getEvent()->getData()),1));

        $storeId = false;
        $customerId = false;

        $customer = $observer->getEvent()->getCustomerDataObject();
        if($customer instanceof \Magento\Customer\Model\Data\Customer){
            $storeId = $customer->getStoreId();
            $customerId = $customer->getId();
        }
        else{
            $customerAddress = $observer->getEvent()->getCustomerAddress();
            if($customerAddress instanceof \Magento\Customer\Model\Address) {
                $customerId = $customerAddress->getCustomerId();
            }
            $this->logger->error('customer address class: '.get_class($customerAddress));

        }

        if($customerId){
            $customer = $this->customerRegistry->retrieve($customerId);
            $this->logger->error('customer class: '.get_class($customer));
            if ($customer instanceof \Magento\Framework\Model\AbstractModel) {
                if(!$storeId) {
                    $storeId = $customer->getStoreId();
                }

                $this->logger->error(get_class($customer));
                $objects = [];
                $data = $this->clangDataHelper->toArray($customer->getDataModel(), $objects);
                foreach($data as $key => $value){
                    $this->logger->debug($key.': '.gettype($value));
                }
                $this->logger->debug(json_encode($data));

                $this->clangCommunication->queueData($storeId, 'update-customer', ['customer'=>$data], 'customer-'.$storeId.'-'.$customerId);
            }
        }
        return $this;

    }

}