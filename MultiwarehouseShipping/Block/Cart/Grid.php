<?php

namespace MW\MultiwarehouseShipping\Block\Cart;

class Grid extends \Magento\Checkout\Block\Cart\Grid
{
    private $multiwarehouseItems = array();

    /**
     * Get array of all items what can be display directly
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getAllItems()
    {
        $items = [];
        $itemsCol = $this->getQuote()->getItemsCollection();
        $itemsCol->clear();
        $itemsCol->setPageSize(false);
        foreach ($itemsCol as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function xlog($message = 'null')
    {
        $log = print_r($message, true);
        $logger = new \Zend\Log\Logger;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/dreyar.log');
        $logger->addWriter($writer);
        $logger->info($log);
    }

    public function getWarehouseItems()
    {
        $this->multiwarehouseItems = [];

        $items = $this->getItems();
        // $items = $this->getAllItems();
        
        foreach ($items as $item) {
            if ($this->hasWarehouseOption($item) && $this->getItemColor($item)) {
	            $key = $this->checkExistItemColor($item);
                if ($key >= 0) {
                    if (!isset($this->multiwarehouseItems[$key])) {
                        $key = $key - 1;
                    }
                    $rootItem = $this->multiwarehouseItems[$key];
                    $options = $rootItem->getData('multiwarehouse_options');
                    if ($result = $this->checkExistWarehouseInOptions($options, $item)) {
                        $optionItems = $result['option']['items'];
                        $optionItems[$this->getItemSize($item)] = array(
                            'item_id' => $item->getItemId(),
                            'size' => $this->getItemSize($item),
                            'qty' => $item->getQty(),
                            'amount' => $item->getRowTotal(),
                            'item' => $item
                        );
                        $options[$result['index']]['items'] = $optionItems;
                    } else {
                        array_push($options, array(
                            'items' => array(
                                $this->getItemSize($item) => array(
                                    'item_id' => $item->getItemId(),
                                    'size' => $this->getItemSize($item),
                                    'qty' => $item->getQty(),
                                    'amount' => $item->getRowTotal(),
                                    'item' => $item
                                )
                            ),
                            'warehouse' => $this->hasWarehouseOption($item)
                        ));
                    }

                    $rootItem->setData('multiwarehouse_options', $options);
                    $this->multiwarehouseItems[$key] = $rootItem;
                } else {
                    $options = [];
                    $options[] = [
                        'items' => array(
                            $this->getItemSize($item) => array(
                                'item_id' => $item->getItemId(),
                                'size' => $this->getItemSize($item),
                                'qty' => $item->getQty(),
                                'amount' => $item->getRowTotal(),
                                'item' => $item
                            )
                        ),
                        'warehouse' => $this->hasWarehouseOption($item)
                    ];
                    $item->setData('multiwarehouse_options', $options);
                    $item->setData('color', $this->getItemColor($item, true));
                    $item->setData('color_id', $this->getItemColor($item));
                    array_push($this->multiwarehouseItems, $item);
                }
            } else {
                array_push($this->multiwarehouseItems, $item);
            }
        }
		
        return $this->multiwarehouseItems;
    }

    /**
     * {@inheritdoc}
     * @since 100.2.0
     */
    protected function _prepareLayout()
    {
        \Magento\Checkout\Block\Cart::_prepareLayout();
        if ($this->isPagerDisplayedOnPage()) {
            $availableLimit = (int)$this->_scopeConfig->getValue(
                self::XPATH_CONFIG_NUMBER_ITEMS_TO_DISPLAY_PAGER,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $itemsCollection = $this->getItemsForGrid();
            /** @var  \Magento\Theme\Block\Html\Pager $pager */
            $pager = $this->getLayout()->createBlock(\Magento\Theme\Block\Html\Pager::class);
            $pager->setAvailableLimit([$availableLimit => $availableLimit])->setCollection($itemsCollection);
            $this->setChild('pager', $pager);
            $itemsCollection->load();
            $this->prepareItemUrls();
        }
        return $this;
    }

    /**
     * Verify if display pager on shopping cart
     * If cart block has custom_items and items qty in the shopping cart<limit from stores configuration
     *
     * @return bool
     */
    private function isPagerDisplayedOnPage()
    {
        return false;
    }

    public function checkExistWarehouseInOptions($options, $item)
    {
        $result = false;
        foreach ($options as $key => $option) {
            if ($option['warehouse'] == $this->hasWarehouseOption($item)) {
                $result = array(
                    'option' => $option,
                    'index' => $key
                );
                break;
            }
        }
        return $result;
    }

    public function checkExistItemColor($item)
    {
        $result = -1;

        foreach ($this->multiwarehouseItems as $key => $whItem) {
            if ($whItem->getProductId() == $item->getProductId() && $this->getItemColor($whItem) == $this->getItemColor($item)) {
                $result = $key;
                return $result;
            }
        }

        return $result;
    }

    public function getItemColor($item, $label = false)
    {
        $color = "";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productConfig = $objectManager->get("Magento\Catalog\Helper\Product\Configuration");
        $options = $productConfig->getOptions($item);

        foreach ($options as $option) {
            if ($option['option_id'] == $objectManager->get("Magento\Eav\Model\ResourceModel\Entity\Attribute")->getIdByCode('catalog_product', 'color') && $option['value']) {
                $color = $option['option_value'];
                if ($label) {
                    $color =$option['value'];
                }

                break;
            }
        }

        return $color;
    }

    public function getItemSize($item)
    {
        $size = "";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productConfig = $objectManager->get("Magento\Catalog\Helper\Product\Configuration");
        $options = $productConfig->getOptions($item);

        foreach ($options as $option) {
            if ($option['option_id'] == $objectManager->get("Magento\Eav\Model\ResourceModel\Entity\Attribute")->getIdByCode('catalog_product', 'size') && $option['value']) {
                $size = $option['option_value'];
                break;
            }
        }

        return $size;
    }

    public function hasWarehouseOption($item)
    {
        $result = false;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productConfig = $objectManager->get("Magento\Catalog\Helper\Product\Configuration");
        $options = $productConfig->getCustomOptions($item);

        foreach ($options as $option) {
            if ($option['label'] == "Warehouse" && $option['value']) {
                $result = $option['value'];
                break;
            }
        }

        return $result;
    }
}