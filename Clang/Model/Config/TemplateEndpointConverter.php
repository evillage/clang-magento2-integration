<?php

namespace Clang\Clang\Model\Config;

class TemplateEndpointConverter implements \Magento\Framework\Config\ConverterInterface
{
    public function convert($source)
    {
        $templates = $source->getElementsByTagName('template');
        $templateEndpoints = [];
        foreach ($templates as $template) {
            $name     = $template->getElementsByTagName('name');
            $endpoint = $template->getElementsByTagName('endpoint');
            $templateEndpoints[$name->item(0)->textContent] = $endpoint->item(0)->textContent;
        }

        return $templateEndpoints;
    }
}
