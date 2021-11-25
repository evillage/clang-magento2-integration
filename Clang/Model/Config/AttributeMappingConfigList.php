<?php

namespace Clang\Clang\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

class AttributeMappingConfigList implements OptionSourceInterface
{
    const DEFAULT_VALUE = '1';
    const DO_NOT_MERGE_SIMPLE_WITH_CONFIGURABLE = '3';

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::DEFAULT_VALUE, 'label' => __('Default')],
            ['value' => self::DO_NOT_MERGE_SIMPLE_WITH_CONFIGURABLE, 'label' => __('Do not merge simple with configurable')]
        ];
    }
}
