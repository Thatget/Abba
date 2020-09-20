<?php

namespace MW\MultiwarehouseShipping\Controller\Cart;

use Magento\Framework\App\ResponseInterface;
use function Composer\Autoload\includeFile;

/**
 * Class Update
 * @package MW\MultiwarehouseShipping\Controller\Cart
 */
class Update extends \Magento\Checkout\Controller\Cart
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
        $response = [];

        try {
            $indexID = '';
            $cartData = $this->getRequest()->getParam('cart');
            if ($cartData && is_array($cartData)) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                        $indexID = $index;
                    }
                }
                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }
                $qty = $data['qty'];
                foreach ($this->cart->getQuote()->getAllItems() as $item){
                    if($item->getId() == $indexID){
                        if ($qty == 0){
                            $this->cart->removeItem($item->getItemId())->save();
                        }else {
                            $item->setQty($qty);
                            $item->save();
                            $this->cart->save();
                        }
                    }
                }

//                $cartData = $this->cart->suggestItemsQty($cartData);
//                $this->cart->updateItems($cartData)->saveQuote()->save();
                $response = [
                    'message' => __("Quantity updated"),
                    'updated' => true
                ];
            } else {
                $cartAddData = $this->getRequest()->getParam('cartAdd');
                $sizeAttributeId = $this->_objectManager->get("Magento\Eav\Model\ResourceModel\Entity\Attribute")->getIdByCode('catalog_product', 'size');
                $colorAttributeId = $this->_objectManager->get("Magento\Eav\Model\ResourceModel\Entity\Attribute")->getIdByCode('catalog_product', 'color');

                $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
                $productId = $cartAddData['productId'];
                $qty = $cartAddData['qty'];
                $color = $cartAddData['color'];

                $size = $cartAddData['size'];
                $warehouse = $cartAddData['warehouse'];

                $ware_house_option_id = NULL;
                // prepare buyRequest
                $buyRequest = new \Magento\Framework\DataObject();

                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($storeId)->load($productId);
                $customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);

                $ware_house_option_id = NULL;

                foreach ($customOptions->getData() as $co) {
                    if ($co['title'] == "Warehouse") {
                        $ware_house_option_id = $co['option_id'];
                    }
                }

                $buyRequest->setData([
                    'product' => $product->getId(),
                    'qty' => $qty,
                    'super_attribute' => [
                        $sizeAttributeId => $size,
                        $colorAttributeId => $color,
                    ],
                    'options' => [
                        $ware_house_option_id => $warehouse,
                    ]
                ]);

                $this->cart->addProduct($product, $buyRequest);
                $cart = $this->cart->save();

                $item = $cart->getQuote()->getItemsCollection()->getLastItem();

                $response = [
                    'message' => __("Product added"),
                    'updated' => true,
                    'added_item_id' => $item->getParentItemId()
                ];
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
            );

            $response = [
                'message' => $e->getMessage(),
                'updated' => false
            ];
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            $response = [
                'message' => $e->getMessage(),
                'updated' => false
            ];
        }

        $jsonHelper = $this->_objectManager->create("Magento\Framework\Json\Helper\Data");
        $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
    }
}
