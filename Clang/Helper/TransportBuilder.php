<?php

namespace Clang\Clang\Helper;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder {

    protected $logger;
    protected $clangCommunication;
    protected $clangDataHelper;
    protected $configReader;

    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        \Clang\Clang\Helper\ClangCommunication $clangCommunication,
        \Clang\Clang\Helper\Data $clangDataHelper,

        \Psr\Log\LoggerInterface $logger,
        ScopeConfigInterface $configReader
    ) {
        parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory, $logger);

        $this->configReader       = $configReader;
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper    = $clangDataHelper;
        $this->logger = $logger;

        $this->logger->info('TRANSPORTBUILDER: MY TRANSPORTBUILDER');

    }


/*
    protected function enrichLinks($origObject){
        $data = [];
        $reflObj = new \ReflectionObject($origObject);
        foreach($reflObj->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            try{
                if(!$method->isStatic() && $method->getNumberOfParameters() == 0 && preg_match('/^get(\w*Link)$/', $method->name, $matches)){
                    $varName = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $matches[1]));
                    $data[$varName] = $method->invoke($origObject);
                }
            }
            catch(\Exception $e){
                // Ignore
            }
        }
        return $data;
    }
/*
    protected function toArray($data, array &$objects){
        if(is_array($data)){
            foreach($data as &$value){
                $value = $this->toArray($value, $objects);
            }
        }
        elseif(is_object($data) && !isset($objects[spl_object_hash($data)])){
            $objects[spl_object_hash($data)] = true;
            $origObject = $data;
            if($data instanceof \Magento\Framework\Model\AbstractModel && method_exists($data, 'getData')){
                $data = $this->toArray($data->getData(), $objects);
                $data = array_merge($this->enrichLinks($origObject), $data);
            }
            elseif($data instanceof \Magento\Framework\DataObject){
                $data = $this->toArray($data->getData(), $objects);
                $data = array_merge($this->enrichLinks($origObject), $data);
            }
            else{

                $this->logger->info('UNKNOWN OBJECT: '.get_class($data));
            }

            /*
            else{
                $result = [];
                foreach($data as $key => $value){
                    $result[$key] = $this->toArray($value, $objects);
                }
                return $result;
            }
            * /
        }
        return $data;
    }*/

    /**
     * Get mail transport
     *
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function getTransport()
    {
        $this->logger->info('TRANSPORTBUILDER: GETTRANSPORT');
        $this->logger->info('TRANSPORTBUILDER: '.$this->templateIdentifier);
        $this->logger->info('TRANSPORTBUILDER: '.implode(', ',array_keys($this->templateVars)));
        $this->logger->info('TRANSPORTBUILDER: '.implode(', ',array_keys($this->templateOptions)));
        $data = [];
        foreach($this->templateOptions as $key => $value){
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if($key == 'store' && is_scalar($value)) $storeId = $value;
        }
        foreach($this->templateVars as $key => $value){
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if($key == 'store' && is_scalar($value)) $storeId = $value;
        }

        if($storeId){
            $data['store_id'] = $storeId;
        }

        $this->logger->info('TRANSPORTBUILDER: STORE: '.$storeId);

        $endpoint = '';
        switch($this->templateIdentifier){
            case 'sales_email_order_template':
            case 'sales_email_order_guest_template':{
                $endpoint = 'order';
                break;
            }
            case 'sales_email_order_comment_template':
            case 'sales_email_order_comment_guest_template':{
                $endpoint = 'order-comment';
                break;
            }
            case 'sales_email_invoice_template':
            case 'sales_email_invoice_guest_template':{
                $endpoint = 'invoice';
                break;
            }
            case 'sales_email_invoice_comment_template':
            case 'sales_email_invoice_comment_guest_template':{
                $endpoint = 'invoice-comment';
                break;
            }
            case 'sales_email_creditmemo_template':
            case 'sales_email_creditmemo_guest_template':{
                $endpoint = 'creditmemo';
                break;
            }
            case 'sales_email_creditmemo_comment_template':
            case 'sales_email_creditmemo_comment_guest_template':{
                $endpoint = 'creditmemo-comment';
                break;
            }
            case 'sales_email_shipment_template':
            case 'sales_email_shipment_guest_template':{
                $endpoint = 'shipment';
                break;
            }
            case 'sales_email_shipment_comment_template':
            case 'sales_email_shipment_comment_guest_template':
            {
                $endpoint = 'shipment-commment';
                break;
            }
            default: {
                $endpoint = preg_replace('/_template$/', '', $this->templateIdentifier);
                break;
            }
        }

        $this->clangCommunication->clearQueue();
        $this->clangCommunication->postData($storeId, $endpoint, $data);

        $disableMail = $this->configReader->getValue('clang/clang/disable_mail/'.$endpoint, ScopeInterface::SCOPE_STORES, $storeId);

        if($disableMail){
            $this->logger->info('TRANSPORTBUILDER: DUMMY: '.$storeId);
            return new DummyTransport();
        }
        else{
            $this->logger->info('TRANSPORTBUILDER: NO DUMMY: '.$storeId);
            return parent::getTransport();
        }
    }

}