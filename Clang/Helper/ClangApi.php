<?php
/**
 * Copyright © 2015 Clang . All rights reserved.
 */
namespace Clang\Clang\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Module\ModuleListInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Helper\Context as HelperContext;
use \Magento\Framework\App\ProductMetadataInterface;
use \Magento\Store\Model\ScopeInterface;

class ClangApi extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'Clang_Clang';

    protected $configReader;
    protected $storeManager;
    protected $moduleList;
    protected $productMetadata;
    protected $callLogFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HelperContext $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        \Clang\Clang\Model\CallLogFactory $callLogFactory
    ) {
        parent::__construct($context);

        $this->configReader    = $context->getScopeConfig();
        $this->storeManager    = $storeManager;
        $this->moduleList      = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->callLogFactory  = $callLogFactory;
    }

    public function getConnectedStoreIds()
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            if ($this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId)) {
                $storeIds[] = $storeId;
            }
        }
        return $storeIds;
    }

    public function getExtensionVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    public function postStatus()
    {
        $results = [];
        foreach ($this->getConnectedStoreIds() as $storeId) {
            try {
                $data = [
                    'base_url'          => $this->storeManager->getStore()->getBaseUrl(),
                    'extension_version' => $this->getExtensionVersion(),
                    'magento_version'   => $this->getMagentoVersion(),
                    'store_id'          => $storeId
                ];

                $results[] = $this->post($storeId, 'status', '', $data);
            } catch (\Exception $e) {
                $results[] = [$e->getCode(), $e->getMessage()];
            }
        }
        return $results;
    }

    public function postData($storeId, $endpoint, $data)
    {
        $log = $this->logCall($storeId, $endpoint, '', $data);
        try {
            list($response_code, $response) = $this->post(
                $storeId,
                $endpoint,
                '',
                $data,
                ['X-Reference'=>$log->getId(), 'X-Identifier'=>'Magento Extension '.$this->getExtensionVersion()]
            );

            $log->setData('response_code', $response_code);
            $log->setData('response', $response);
            $log->save();
        } catch (\Exception $e) {
            $log->setData('response_code', $e->getCode());
            $log->setData('response', $e->getMessage());
            $log->save();
        }
    }

    protected function logCall($storeId, $endpoint, $path, $data)
    {
        return $this->callLogFactory->create()
            ->setData('endpoint', $endpoint.($path?'/'.$path:''))
            ->setData('response_code', '')
            ->setData('store_id', $storeId)
            ->setData('response', '')
            ->setData('data', json_encode($data))
            ->setData('call_time', date('Y-m-d H:i:s'))
            ->save();
    }

    protected function post($storeId, $endpoint, $path, $data, $headers = [])
    {
        return $this->request($storeId, 'POST', $endpoint, $path, $data, $headers);
    }

    protected function request($storeId, $method, $endpointName, $path = '', $data = null, $headers = [])
    {
        try {
            $token       = $this->configReader->getValue(
                'clang/clang/clang_token',
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            $endpoint    = $this->configReader->getValue(
                'clang/clang/endpoint/'.$endpointName,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            if (!$endpoint) {
                $endpoint = $this->configReader->getValue(
                    'clang/clang/endpoint/generic',
                    ScopeInterface::SCOPE_STORES,
                    $storeId
                );
                $endpoint .= str_replace('_', '-', $endpointName);
            }

            $url         = $endpoint.($path?'/'.$path:'').'?token='.$token;
            $data_string = json_encode($data);

            // Not using \Magento\Framework\HTTP\Client\Curl because we need to send json data, and the Magento Curl
            // client doesn't support it
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $headers['Content-Type'] = 'application/json';
                $headers['Content-Length'] = strlen($data_string);
            }

            if ($headers) {
                $h = [];
                foreach ($headers as $key => $value) {
                    $h[] = $key.': '.$value;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
            }

            $response = curl_exec($ch);

            // Check HTTP status code
            if (curl_errno($ch)) {
                curl_close($ch);
                throw new \Exception(curl_error($ch), 1000+curl_errno($ch));
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == 200) {
                $result = json_decode($response, true);
                return [$http_code, $result];
            } else {
                throw new \Exception($response, $http_code);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 2000+$e->getCode());
        }
    }
}
