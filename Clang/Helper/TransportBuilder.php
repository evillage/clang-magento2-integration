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
        parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory);

        $this->configReader       = $configReader;
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper    = $clangDataHelper;
        $this->logger = $logger;

        $this->logger->info('TRANSPORTBUILDER: MY TRANSPORTBUILDER');

    }

    /**
     * Get mail transport
     *
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function getTransport()
    {
        $templateIdentifier = $this->templateIdentifier;
        if(is_numeric($templateIdentifier) && $this->getTemplate()){
            $tpl = $this->getTemplate()->load($templateIdentifier);
            if(is_callable([$tpl, 'getTemplateCode'])){
                $templateIdentifier = $tpl->getTemplateCode();
            }
        }
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
        switch($templateIdentifier){
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
            case 'newsletter_subscription_success_email_template': {
                $endpoint = 'newsletter-subscribe';
                break;
            }
            case 'customer_create_account_email_no_password_template': {
                $endpoint = 'create-account-no-password';
                break;
            }
            case 'customer_password_forgot_email_template': {
                $endpoint = 'customer-password-forgot';
                break;
            }
            case 'customer_create_account_email_template': {
                $endpoint = 'customer-create-account';
                break;
            }
            case 'customer_password_remind_email_template': {
                $endpoint = 'customer-password-remind';
                break;
            }
            case 'newsletter_subscription_un_email_template': {
                $endpoint = 'newsletter-unsubscribe';
                break;
            }
            case 'sendfriend_email_template': {
                $endpoint = 'sendfriend';
                break;
            }
            case 'update_customer_template': {
                $endpoint = 'update-customer';
                break;
            }
            case 'wishlist_email_email_template': {
                $endpoint = 'wishlist';
                break;
            }
            case 'customer_account_information_change_email_template': {
                $endpoint = 'customer-change-email';
                break;
            }
            default: {
                $endpoint = preg_replace('/_template$/', '', $templateIdentifier);
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
