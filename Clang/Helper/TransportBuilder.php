<?php

namespace Clang\Clang\Helper;

use Clang\Clang\Config;
use Clang\Clang\Model\Config\AttributeMappingConfigList;
use Clang\Clang\Model\Product\ProductAttributeMapper;
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

    /**
     * @var ProductAttributeMapper
     */
    private $productAttributeMapper;
    /**
     * @var Config
     */
    private $config;

    /**
     * TransportBuilder constructor.
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param ClangCommunication $clangCommunication
     * @param Data $clangDataHelper
     * @param TemplateEndpointConfig $templateEndpoints
     * @param ScopeConfigInterface $configReader
     * @param ProductAttributeMapper $productAttributeMapper
     * @param Config $config
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        ClangCommunication $clangCommunication,
        Data $clangDataHelper,
        TemplateEndpointConfig $templateEndpoints,
        ScopeConfigInterface $configReader,
        ProductAttributeMapper $productAttributeMapper,
        Config $config
    ) {
        parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory);

        $this->configReader       = $configReader;
        $this->clangCommunication = $clangCommunication;
        $this->clangDataHelper    = $clangDataHelper;
        $this->templateEndpoints  = $templateEndpoints;
        $this->productAttributeMapper = $productAttributeMapper;
        $this->config = $config;
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
        $storeId = null;

        foreach ($this->templateOptions as $key => $value) {
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if ($key === 'store' && is_scalar($value)) {
                $storeId = $value;
            }
        }
        foreach ($this->templateVars as $key => $value) {
            unset($objects);
            $objects = [];
            $data[$key] = $this->clangDataHelper->toArray($value, $objects);
            if ($key === 'store' && is_scalar($value)) {
                $storeId = $value;
            }
        }

        if ($storeId) {
            $data['store_id'] = $storeId;
        }

        // Specific solution to be compatible with the Buckaroo Magento 2 extension
        // It solves the issue that there are no order items in $data
        if (!isset($data['order']['items']) && in_array($templateIdentifier, ['sales_email_order_guest_template', 'sales_email_order_template'])) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->templateVars['order'];
            if ($items = $order->getAllItems()) {
                unset($objects);
                $objects = [];
                $data['order']['items'] = $this->clangDataHelper->toArray($items, $objects);
            }
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

        if (isset($data['order']) && (!isset($data['store']['code']) || $this->config->getAttributeMappingMode($data['store']['code']) !== AttributeMappingConfigList::DO_NOT_MERGE_SIMPLE_WITH_CONFIGURABLE)) {
            $data['order']['items'] = $this->productAttributeMapper->setProductsToMap($data['order']);
        }

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
        }

        return parent::getTransport();
    }
}
