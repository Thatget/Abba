<?php

namespace MW\MultiwarehouseShipping\Observer;

use Magento\Framework\Event\ObserverInterface;

class QuoteSubmitBefore implements ObserverInterface
{

    /**
     * Add coordinates to warehouse address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        $order = $observer->getOrder();

        $order->setData('multiwarehouse_shipping', $quote->getData('multiwarehouse_shipping'));

        return $this;
    }
}
