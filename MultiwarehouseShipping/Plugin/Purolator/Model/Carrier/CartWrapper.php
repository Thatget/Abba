<?php

namespace MW\MultiwarehouseShipping\Plugin\Purolator\Model\Carrier;

class CartWrapper
{
    public function afterItemCallback(\Meetanshi\Purolator\Model\Carrier\Purolator $subject, $result, $productData, $config)
    {
        $separateItems = $config->request->getAllItems();
        $invoiceItem = null;
        foreach ($separateItems as $item) {
            $invoiceItem = $item;
            break;
        }
        $quote = $invoiceItem->getQuote();
        $items = $quote->getAllVisibleItems();

        $config->runningPrice = 0;
        foreach ($items as $item) {
            $config->runningPrice += $item->getPrice() * $item->getQty();
        }

        return $result;
    }
}