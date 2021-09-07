<?php

namespace Clang\Clang\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

class AttributeMappingConfigList implements OptionSourceInterface
{
    const DEFAULT_VALUE = '1';
    const MERGE_SIMPLE_WITH_CONFIGURABLE = '2';

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::DEFAULT_VALUE, 'label' => __('Default')],
            ['value' => self::MERGE_SIMPLE_WITH_CONFIGURABLE, 'label' => __('Merge simple with configurable')]
        ];
    }
}
