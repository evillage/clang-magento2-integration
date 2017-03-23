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
use \Psr\Log\LoggerInterface;
use \Magento\Store\Model\ScopeInterface;

class ClangApi extends \Magento\Framework\App\Helper\AbstractHelper
{

    const MODULE_NAME = 'Clang_Clang';

    protected $configReader;
    protected $storeManager;
    protected $moduleList;
    protected $productMetadata;
    protected $logger;
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

        $this->configReader         = $context->getScopeConfig();
        $this->storeManager         = $storeManager;
        $this->moduleList           = $moduleList;
        $this->productMetadata      = $productMetadata;
        $this->callLogFactory       = $callLogFactory;

        $this->logger = $context->getLogger();
        $this->logger->info('TEST');
    }

    public function getConnectedStoreIds(){
        $storeIds = [];
        foreach($this->storeManager->getStores() as $store){
            $storeId = $store->getId();
            if($this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId)){
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

    public function postCronStatus(){
        foreach($this->getConnectedStoreIds() as $storeId){
            $data = [
                'base_url'          => $this->storeManager->getStore()->getBaseUrl(),
                'extension_version' => $this->getExtensionVersion(),
                'magento_version'   => $this->getMagentoVersion(),
                'store_id'          => $storeId
            ];

            $this->post($storeId, 'cron-status', '', $data);
        }
    }

    public function postNewOrder($storeId, $data){
        $logId = $this->logCall($storeId, 'new-order', '', $data);
        return $this->post($storeId, 'new-order', '', $data, ['X-Reference'=>$logId]);
    }

    public function postData($storeId, $endpoint, $data){
        $logId = $this->logCall($storeId, $endpoint, '', $data);
        return $this->post($storeId, $endpoint, '', $data, ['X-Reference'=>$logId]);
    }

    protected function logCall($storeId, $endpoint, $path, $data){
        return $this->callLogFactory->create()
            ->setData('endpoint', $endpoint.($path?'/'.$path:''))
            ->setData('response_code', '')
            ->setData('store_id', $storeId)
            ->setData('response', '')
            ->setData('data', json_encode($data))
            ->setData('call_time', date('Y-m-d H:i:s'))
            ->save()
            ->getId();

    }

    protected function post($storeId, $endpoint, $path, $data, $headers = []){
        return $this->request($storeId, 'POST', $endpoint, $path, $data, $headers);
    }

    protected function request($storeId, $method, $endpointName, $path = '', $data = null, $headers = []) {
        $this->logger->info('REQUEST');
        try{

            $token       = $this->configReader->getValue('clang/clang/clang_token', ScopeInterface::SCOPE_STORES, $storeId);
            $endpoint    = $this->configReader->getValue('clang/clang/endpoint/'.$endpointName, ScopeInterface::SCOPE_STORES, $storeId);
            if(!$endpoint){
                $endpoint = $this->configReader->getValue('clang/clang/endpoint/generic', ScopeInterface::SCOPE_STORES, $storeId).str_replace('_','-',$endpointName);
            }

            $this->logger->info($token);
            $this->logger->info($endpoint);

            $url         = $endpoint.($path?'/'.$path:'').'?token='.$token;
            $data_string = json_encode($data);

            $this->logger->info($url);
            $this->logger->info($data_string);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if($data){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $headers['Content-Type'] = 'application/json';
                $headers['Content-Length'] = strlen($data_string);
            }

            if($headers){
                $h = [];
                foreach($headers as $key => $value){
                    $h[] = $key.': '.$value;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
                $this->logger->info(serialize($h));
            }

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $headerData){
                return strlen($headerData);
            });
            $response = curl_exec($ch);

            $this->logger->info($response);

            // Check HTTP status code
            if (!curl_errno($ch)) {
                $info = curl_getinfo($ch);
                $http_code = $info['http_code'];
                $this->logger->info(print_r($info,1));
                curl_close($ch);
                switch ($http_code) {
                    case 200:  # OK
                        $this->logger->info('SUCCESS: '.$http_code);
                        $result = json_decode($response, true);
                        return $result;
                    default:
                        $this->logger->info('Unexpected HTTP code: '.$http_code);
                }
            }
            else{
                $this->logger->info('CURL ERR: '.curl_errno($ch));
            }

            return;
        }
        catch(\Exception $e){
            $this->logger->info('EXCEPTION: '.$e->getMessage());
            $this->logger->info('EXCEPTION: '.$e->getTraceAsString());
        }

    }
}