<?php

namespace MW\MultiwarehouseShipping\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\ResponseInterface;

/**
 * Class Delete
 * @package MW\MultiwarehouseShipping\Controller\Cart
 */
class Delete extends \Magento\Checkout\Controller\Cart
{
    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */


    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('product_id');
        $color = $this->getRequest()->getParam('color');
        $size = $this->getRequest()->getParam('size');
		$warehouse = $this->getRequest()->getParam('warehouse');

        if ($productId) {
            try {
                $items = $this->cart->getQuote()->getAllVisibleItems();
                if ($size){
                    foreach ($items as $item) {
                        if ($item->getProduct()->getId() == $productId) {
                            if ($this->getItemType($item,'color') == $color) {
                                if ($this->getItemType($item, 'size') == $size) {
									if($warehouse){
										$options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
										if (($options['options'][0]['label'] == 'Warehouse') && ($options['options'][0]['value'] == $warehouse)) {
											$this->cart->removeItem($item->getItemId())->save();
										}
									}else{
										$this->cart->removeItem($item->getItemId());
									}
                                }
                            }
                        }
                    }
					if(!$warehouse)$this->cart->save();
                }else{
                    foreach ($items as $item) {
                        if ($item->getProduct()->getId() == $productId) {
                            if ($this->getItemType($item, 'color') == $color) {
                                $this->cart->removeItem($item->getItemId());
                            }
                        }
                    }
                    $this->cart->save();
                }
                $this->cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals()->save();

            } catch (\Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
                $this->messageManager->addError(__('We can\'t remove the item.'));
                //$this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $redirectUrl = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('checkout/cart/');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($redirectUrl));
    }

    public function getItemType($item,$type)
    {
        $set = "";

        $productConfig = $this->_objectManager->get("Magento\Catalog\Helper\Product\Configuration");
        $options = $productConfig->getOptions($item);

        foreach ($options as $option) {
            if ($option['option_id'] == $this->_objectManager->get("Magento\Eav\Model\ResourceModel\Entity\Attribute")->getIdByCode('catalog_product', $type) && $option['value']) {
                $set = $option['option_value'];
                break;
            }
        }

        return $set;
    }
}
