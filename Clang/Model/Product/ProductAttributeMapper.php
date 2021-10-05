<?php

namespace Clang\Clang\Model\Product;

use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductAttributeMapper
{
    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $productRepositoryFactory;

    /**
     * ProductAttributeMapper constructor.
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     */
    public function __construct
    (
        ProductRepositoryInterfaceFactory $productRepositoryFactory
    ) {
        $this->productRepositoryFactory = $productRepositoryFactory;
    }


    /**
     * Loop through order items and add images to configurable item and merge attributes from simple and configurable
     *
     * @param array $order
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    public function setProductsToMap(array $order)
    {
        if (!isset($order['items'])) {
            return '';
        }

        $items = $order['items'];

        foreach ($items as &$item) {
            if ($item['product_type'] === 'configurable') {
                $item = $this->addConfigurableImage($item);
            } elseif ($item['product_type'] === 'simple') {
                if (!isset($items[$item['parent_item_id']])) {
                    continue;
                }
                $item = $this->mapConfigurableAttributesToSimple($items[$item['parent_item_id']], $item);
            }
        }

        return $items;
    }

    /**
     * Get media images from configurable product
     *
     * @param $configurable
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function addConfigurableImage ($configurable) {
           $product = $this->productRepositoryFactory->create()->getById($configurable['product_id']);
           if ($product->getMediaGalleryImages()) {
               $configurable['media_image_gallery'] = $product->getMediaGalleryImages()->getItems();
           }

        return $configurable;
    }

    /**
     * Merge simple product with parent, when simple key value is null then replace with key value of its parent.
     * When parent has keys that simple does not have then add them to the simple product.
     *
     * @param $parentItem
     * @param $item
     * @return array
     */
    public function mapConfigurableAttributesToSimple($parentItem, $item): array
    {
        foreach ($parentItem as $key => $parentData){
            $item[$key] = $item[$key] ?? $parentData;
        }

        return $item;
    }
}
