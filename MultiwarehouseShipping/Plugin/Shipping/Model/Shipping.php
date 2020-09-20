<?php
namespace MW\MultiwarehouseShipping\Plugin\Shipping\Model;

use \Magento\Checkout\Model\Session as CheckoutSession;

class Shipping
{
    private $warehouseFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    private $multiShipping;

    private $resultFactory;

    private $checkoutSession;

    public function __construct(
        \Amasty\MultiInventory\Model\WarehouseFactory $warehouseFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MW\MultiwarehouseShipping\Model\MultiShippingFactory $multiShipping,
        \Magento\Shipping\Model\Rate\ResultFactory $resultFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->warehouseFactory = $warehouseFactory;
        $this->scopeConfig = $scopeConfig;
        $this->multiShipping = $multiShipping;
        $this->resultFactory = $resultFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundCollectRates(
        \Magento\Shipping\Model\Shipping $shipping,
        \Closure $process,
        \Magento\Quote\Model\Quote\Address\RateRequest $request
    )
    {
        $quote = $this->checkoutSession->getQuote();
        $warehouseItems = $this->getWarehouseItems($quote);

        if (empty($warehouseItems)) {
            return $process($request);
        }

        $warehouseNames = array();
        foreach ($warehouseItems as $key => $items) {
            $warehouseNames[] = $key;
        }

        $warehouses = $this->warehouseFactory->create()->getCollection()->addFieldToFilter('title', array('in' => $warehouseNames));
        $results = $this->resultFactory->create();
        foreach ($warehouses as $warehouse) {
            $request = $this->changeRequestAddress($request, $warehouse->getData());
            $request  = $this->changeRequestItems($request, $warehouseItems[$warehouse->getTitle()], $quote);
            $shipment = $this->shipmentCalculate($request, $process);
            foreach ($shipment->getAllRates() as $rate) {
                // $rate->setData('carrier_title', $warehouse->getTitle());
                // $rate->setData('method', $rate->getData('method')."_".$warehouse->getCode());
//                $rate->setData('carrier_title', $warehouse->getTitle()."_".$warehouse->getCode());
                $rate->setData('carrier_title', $warehouse->getTitle()."_".$warehouse->getCode()."_multi_".$rate->getCarrierTitle());
                $rate->setData('method', $rate->getData('method'));
                $results->append($rate);
            }
        }
        $shipping->getResult()->append($results);

        return $shipping;
    }

    private function shipmentCalculate(\Magento\Quote\Model\Quote\Address\RateRequest $request, $work)
    {
        $storeId = $request->getStoreId();
        /** @var \MW\MultiwarehouseShipping\Model\MultiShipping $multiShipping */
        $multiShipping = $this->multiShipping->create();

        $limitCarrier = $request->getLimitCarrier();
        if (!$limitCarrier) {
            $carriers = $this->getCarriers($storeId);

            foreach ($carriers as $carrierCode => $carrierConfig) {
                $multiShipping->collectCarrierRates($carrierCode, $request);
            }
        } else {
            if (!is_array($limitCarrier)) {
                $limitCarrier = [$limitCarrier];
            }
            foreach ($limitCarrier as $carrierCode) {
                $carrierConfig = $this->getCarriers($storeId, $carrierCode);
                if (!$carrierConfig) {
                    continue;
                }
                $multiShipping->collectCarrierRates($carrierCode, $request);
            }
        }

        return $multiShipping->getResult();
    }

    /**
     * @param int         $storeId
     * @param string|null $carrierCode
     *
     * @return array
     */
    private function getCarriers($storeId, $carrierCode = null)
    {
        $configPath = 'carriers';
        if ($carrierCode !== null) {
            $configPath .= '/' . $carrierCode;
        }
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function changeRequestAddress($request, $data)
    {
        $request->setCountryId(
            $data['country']
        )->setRegionId(
            $data['state']
        )->setCity(
            $data['city']
        )->setPostcode(
            $data['zip']
        )->setOrigCountryId(
            $data['country']
        )->setOrigRegionId(
            $data['state']
        )->setOrigCountry(
            $data['country']
        )->setOrigRegionCode(
            $data['state']
        )->setOrigRegion(
            $data['state']
        )->setOrigCity(
            $data['city']
        )->setOrigPostcode(
            $data['zip']
        );

        return $request;
    }

    private function changeRequestItems($request, $items, $quote)
    {
        $request->setAllItems($items);
        $data = $this->calcData($items, $quote);
        $request->setPackageWeight($data['package_weight']);

        return $request;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @param \Magento\Quote\Model\Quote       $quote
     *
     * @return array
     */
    private function calcData($items, $quote)
    {
        $data = [
            'base_row_total' => 0,
            'base_discount_amount' => 0,
            'qty' => 0,
            'base_subtotal_incl_tax' => 0,
            'weight' => 0,
            'package_weight' => 0
        ];
        foreach ($items as $item) {
            $element = $item;
            if ($item->getParentItemId()) {
                $element = $quote->getItemById($item->getParentItemId());
            }

            $element->calcRowTotal();
            $data['base_row_total'] += $element->getBaseRowTotal();
            $data['base_discount_amount'] += ($element->getBaseRowTotal()
                - ($element->getBaseRowTotal() * $element->getDiscountPercent()));
            $data['qty'] += $element->getQty();
            $data['base_subtotal_incl_tax'] += $element->getBasePriceInclTax() * $element->getQty();
            if ($element->getWeight()) {
                $data['weight'] += $element->getWeight();
            }
            $data['package_weight'] = $element->getRowWeight() ?: $element->getWeight();
        }

        return $data;
    }

    private function getWarehouseItems($quote)
    {
        $items = $quote->getAllVisibleItems();
        $data = array();

        foreach ($items as $item) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helperConfig = $objectManager->get('Magento\Catalog\Helper\Product\Configuration');

            $options = $helperConfig->getCustomOptions($item);

            foreach ($options as $option) {
                if ($option['label'] == "Warehouse") {
                    $data[$option['value']][] = $item;
                }
            }
        }

        // if (empty($data)) {
        //     $defaultWarehouse = "Montreal";
        //     foreach ($items as $item) {
        //         $data[$defaultWarehouse][] = $item;
        //     }
        // }

        return $data;
    }
}