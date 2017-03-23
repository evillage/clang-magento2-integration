<?php

namespace Clang\Clang\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Event123 implements ObserverInterface {
    protected $logger;
    protected $clangCommunication;
    public function __construct(
        \Clang\Clang\Helper\ClangCommunication $clangCommunication,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->clangCommunication = $clangCommunication;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        /*
        TODO: Dit is niet meer nodig. Want we doen t via de transport.

        $this->logger->info('HALLO SIMON');
        $this->logger->debug('HALLO SIMON');
        $this->logger->error('HALLO SIMON');
        $this->logger->error('observer class: '.print_r(array_keys($observer->getEvent()->getData()),1));


        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Magento\Framework\Model\AbstractModel) {

            $this->logger->error(get_class($order));
            $orderData = $this->toArray($order);
            foreach($orderData as $key => $value){
                $this->logger->debug($key.': '.gettype($value));
            }
            $this->logger->debug(json_encode($orderData));

            $this->clangCommunication->postNewOrder($orderData['store_id'], $orderData);
            //if($order->getState() == 'canceled' || $order->getState() == 'closed') {
                //Your code here
            //}
        }
        return $this;
        */
    }

    protected $objects = [];

    protected function toArray($data){
        if(is_array($data)){
            foreach($data as &$value){
                $value = $this->toArray($value);
            }
        }
        elseif(is_object($data) && !isset($this->objects[spl_object_hash($data)])){
            $this->objects[spl_object_hash($data)] = true;
            $this->logger->debug(get_class($data));
            if(method_exists($data, 'getData')){
                $data = $this->toArray($data->getData());
            }
        }
        return $data;
    }
}