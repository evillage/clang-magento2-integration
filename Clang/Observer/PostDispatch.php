<?php

namespace Clang\Clang\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PostDispatch implements ObserverInterface {
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
        $this->clangCommunication->postQueue();

        return $this;

    }

}