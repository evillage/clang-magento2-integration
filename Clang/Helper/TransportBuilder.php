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
use Clang\Clang\Model\Config\TemplateEndpointData as TemplateEndpointConfig;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    protected $clangCommunication;
    protected $clangDataHelper;
    protected $configReader;
    protected $templateEndpoints;

    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        \Clang\Clang\Helper\ClangCommunication $clangCommunication,
        \Clang\Clang\Helper\Data $clangDataHelper,
        TemplateEndpointConfig $templateEndpoints,
        ScopeConfigInterface $configReader
    ) {
        parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory);

        $this->configReader       = $configReader;
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper    = $clangDataHelper;
        $this->templateEndpoints  = $templateEndpoints;
    }

    /**
     * Collecting all message related data to send to Clang to keep the Clang database
     * up to date and possibly trigger mail or other campaigns.
     *
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function getTransport()
    {
        // The template identifier is needed to identify the call to Clang. If it is a custom
        // template get the template code instead of a numeric identifier.
        $templateIdentifier = $this->templateIdentifier;
        if (is_numeric($templateIdentifier) && $this->getTemplate()) {
            $tpl = $this->getTemplate()->load($templateIdentifier);
            if (is_callable([$tpl, 'getTemplateCode'])) {
                $templateIdentifier = $tpl->getTemplateCode();
            }
        }

        // Collect all data from the templateoptions and template vars. Convert it to scalars
        // and arrays to be able to json_encode the data and send it to Clang.
        $data = [];
        foreach ($this->templateOptions as $key => $value) {
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if ($key == 'store' && is_scalar($value)) {
                $storeId = $value;
            }
        }
        foreach ($this->templateVars as $key => $value) {
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if ($key == 'store' && is_scalar($value)) {
                $storeId = $value;
            }
        }

        if ($storeId) {
            $data['store_id'] = $storeId;
        }

        // Some template names are mapped and grouped to shorter endpoint names. We get the
        // endpoint name from the config here. If there is no endpoint name in the config
        // we just clean up the template name and use it as the endpoint name, Clang does
        // understand this.
        $endpoint = $this->templateEndpoints->get($templateIdentifier);
        if (empty($endpoint)) {
            $endpoint = preg_replace('/_template$/', '', $templateIdentifier);
        }

        $endpoint = preg_replace('/\s+/', '-', strtolower($endpoint));

        // Send the data to Clang
        $this->clangCommunication->postData($storeId, $endpoint, $data);

        /**
         * When a e-mail campaign is configured and completely working this would have been send to the extension
         * using the api method disable_mails. If this is the case we do not need to send any messages with Magento
         * so we return a dummy transport. If there is no e-mail campaign set up in Clang or if it is temporary
         * unavailable we use Magento's transport to send the messages.
         */
        $disableMail = $this->configReader->getValue(
            'clang/clang/disable_mail/'.$endpoint,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if ($disableMail) {
            return new DummyTransport();
        } else {
            return parent::getTransport();
        }
    }
}
