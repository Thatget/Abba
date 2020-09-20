<?php

namespace MW\MultiwarehouseShipping\Block\Cart\Item\Renderer;

class Configurable extends \Magento\ConfigurableProduct\Block\Cart\Item\Renderer\Configurable
{

    /**
     * Cache key for configurable attributes
     *
     * @var string
     */
    protected $_allConfigurableAttributes = '_cache_instance_all_configurable_attributes';

    public function getProductBasedOnColor($product_id, $color)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->get("Magento\Catalog\Api\ProductRepositoryInterface");

        $product = $productRepository->getById($product_id);
        $productTypeInstance = $product->getTypeInstance();
        // $productTypeInstance->setStoreFilter($storeId, $product);
        $usedProducts = $productTypeInstance->getAllUsedProducts($product);

        $children_products = array();

        foreach ($usedProducts as $child){

            if($child->getColor() == $color) {

                $children_products[] = $child->getData();
            }

        }
        $arrangedData =  $this->arrangeDataBySize($product_id, $children_products);

        return  $arrangedData;
    }

    /**
     * Retrieve configurable attributes data
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute[]
     */
    public function getConfigurableAttributes($product)
    {
        \Magento\Framework\Profiler::start(
            'CONFIGURABLE:' . __METHOD__,
            ['group' => 'CONFIGURABLE', 'method' => __METHOD__]
        );
        if (!$product->hasData($this->_allConfigurableAttributes)) {
            // for new product do not load configurable attributes
            if (!$product->getId()) {
                return [];
            }
            $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productTypeInstance = $_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
            $configurableAttributes = $productTypeInstance->getConfigurableAttributeCollection($product);
            $configurableAttributes->orderByPosition()->load();
            $product->setData($this->_allConfigurableAttributes, $configurableAttributes);
        }
        \Magento\Framework\Profiler::stop('CONFIGURABLE:' . __METHOD__);
        return $product->getData($this->_allConfigurableAttributes);
    }

    /**
     * Retrieve Configurable Attributes as array
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getConfigurableAttributesAsArray($product)
    {
        $res = [];
        foreach ($this->getConfigurableAttributes($product) as $attribute) {
            $eavAttribute = $attribute->getProductAttribute();
            $storeId = 0;
            if ($product->getStoreId() !== null) {
                $storeId = $product->getStoreId();
            }
            $eavAttribute->setStoreId($storeId);
            /* @var $attribute \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute */
            $res[$eavAttribute->getId()] = [
                'id' => $attribute->getId(),
                'label' => $attribute->getLabel(),
                'use_default' => $attribute->getUseDefault(),
                'position' => $attribute->getPosition(),
                'values' => $attribute->getOptions() ? $attribute->getOptions() : [],
                'attribute_id' => $eavAttribute->getId(),
                'attribute_code' => $eavAttribute->getAttributeCode(),
                'frontend_label' => $eavAttribute->getFrontend()->getLabel(),
                'store_label' => $eavAttribute->getStoreLabel(),
                'options' => $eavAttribute->getSource()->getAllOptions(false),
            ];
        }
        return $res;
    }

    public function arrangeDataBySize($product_id, $children_products)
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $product = $_objectManager->get('\Magento\Catalog\Model\Product')->load($product_id);

        $productTypeInstance = $_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        // $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($product);
        $productAttributeOptions = $this->getConfigurableAttributesAsArray($product);

        $sizeOptions = array();

        $i = 0;
        foreach ($productAttributeOptions as $value) {

            if ($value['attribute_code'] == 'size') {

                foreach ($value['options'] as $item) {

                    $sizeOptions[] = $item['value'];
                }
            }

            $i++;
        }

        $order = $sizeOptions;

        usort($children_products, function ($a, $b) use ($order) {
            $pos_a = array_search($a['size'], $order);
            $pos_b = array_search($b['size'], $order);
            return $pos_a - $pos_b;
        });

        return $children_products;
    }

    public function getOptionLabelByValue($attributeCode, $optionId)
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $_product = $_objectManager->create('\Magento\Catalog\Model\Product');
        $isAttributeExist = $_product->getResource()->getAttribute($attributeCode);
        $optionText = '';
        if ($isAttributeExist && $isAttributeExist->usesSource()) {
            $optionText = $isAttributeExist->getSource()->getOptionText($optionId);
        }
        return $optionText;
    }

    public function getItemIdBySizeAndWarehouse($item, $size, $warehouse)
    {
        $itemId = '';

        $warehouseOptions = $item->getMultiwarehouseOptions();
        foreach ($warehouseOptions as $option) {
            if ($option['warehouse'] == $warehouse) {
                $items = $option['items'];
                foreach ($items as $key => $item) {
                    if ($item['size'] == $size) {
                        $itemId = $item['item_id'];
                    }
                }
            }
        }

        return $itemId;
    }

    public function getQtyBySizeAndWarehouse($item, $size, $warehouse)
    {
        $qty = 0;

        $warehouseOptions = $item->getMultiwarehouseOptions();
        foreach ($warehouseOptions as $option) {
            if ($option['warehouse'] == $warehouse) {
                $items = $option['items'];
                foreach ($items as $key => $item) {
                    if ($item['size'] == $size) {
                        $qty = $item['qty'];
                    }
                }
            }
        }

        return $qty;
    }

    public function getSizePrice($item, $size)
    {
        $warehouseOptions = $item->getMultiwarehouseOptions();
        $total = 0;
        foreach ($warehouseOptions as $option) {
            $items = $option['items'];
            foreach ($items as $key => $item) {
                if ($item['size'] == $size) {
                    $total += $item['amount'];
                }
            }
        }

        return $total;
    }

    public function getRowTotalPrice($item, $color)
    {
        $warehouseOptions = $item->getMultiwarehouseOptions();
        $total = 0;
        foreach ($warehouseOptions as $option) {
            $items = $option['items'];
            foreach ($items as $key => $item) {
                $total += $item['amount'];
            }
        }

        return $total;
    }

    public function formatPrice($price)
    {
        if (!$price) {
            return "";
        }

        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $_objectManager->get("Magento\Framework\Pricing\Helper\Data")->currency($price, true, false);
    }

    /**
     * Retrieve item messages
     * Return array with keys
     *
     * text => the message text
     * type => type of a message
     *
     * @return array
     */
    public function getMultiMessages()
    {
        $messages = [];
        $warehouseData = $this->getWarehouseData();
        $items = array();
        foreach ($warehouseData as $warehouse) {
            foreach ($warehouse['items'] as $item) {
                $items[] = $item['item'];
            }
        }

        foreach ($items as $item) {
            $baseMessages = $item->getMessage(false);
            if ($baseMessages) {
                foreach ($baseMessages as $message) {
                    $messages[] = ['text' => $message, 'type' => $item->getHasError() ? 'error' : 'notice'];
                }
            }

            /* @var $collection \Magento\Framework\Message\Collection */
            $collection = $this->messageManager->getMessages(true, 'quote_item' . $item->getId());
            if ($collection) {
                $additionalMessages = $collection->getItems();
                foreach ($additionalMessages as $message) {
                    /* @var $message \Magento\Framework\Message\MessageInterface */
                    $messages[] = [
                        'text' => $this->messageInterpretationStrategy->interpret($message),
                        'type' => $message->getType()
                    ];
                }
            }
            $this->messageManager->getMessages(true, 'quote_item' . $item->getId())->clear();
        }

        return $messages;
    }

    public function getWarehouseData()
    {
        return $this->getItem()->getData("multiwarehouse_options");
    }
}
