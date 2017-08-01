<?php
/**
 * Copyright © 2015 Clang . All rights reserved.
 */
namespace Clang\Clang\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    protected function enrichLinks($origObject)
    {
        $data = [];
        $reflObj = new \ReflectionObject($origObject);
        foreach ($reflObj->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            try {
                if (!$method->isStatic() &&
                    $method->getNumberOfParameters() == 0 &&
                    preg_match('/^get(\w*Link)$/', $method->name, $matches)) {
                    $varName = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $matches[1]));
                    $data[$varName] = $method->invoke($origObject);
                }
            } catch (\Exception $e) {
                // Ignore this. If any links can't be rendered we don't need them.
            }
        }
        return $data;
    }

    public function toArray($data, array &$objects)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->toArray($value, $objects);
            }
        } elseif (is_object($data) && !isset($objects[spl_object_hash($data)])) {
            $objects[spl_object_hash($data)] = true;
            $origObject = $data;
            if ($data instanceof \Magento\Framework\Model\AbstractModel && method_exists($data, 'getData')) {
                $data = $this->toArray($data->getData(), $objects);
                $data = array_merge($this->enrichLinks($origObject), $data);
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $this->toArray($data->getData(), $objects);
                $data = array_merge($this->enrichLinks($origObject), $data);
            } elseif ($data instanceof \Magento\Framework\Api\AbstractSimpleObject) {
                $data = $this->toArray($data->__toArray(), $objects);
                $data = array_merge($this->enrichLinks($origObject), $data);
            }
        }
        return $data;
    }
}
