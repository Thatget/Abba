<?php
namespace MW\MultiwarehouseShipping\Plugin\Quote\Address\Total;

class Shipping extends \Magento\Quote\Model\Quote\Address\Total\Shipping
{
    public function aroundCollect(
        \Magento\Quote\Model\Quote\Address\Total\Shipping $subject,
        \Closure $process,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        if (false) {
            return $process($quote, $shippingAssignment, $total);
        }

        \Magento\Quote\Model\Quote\Address\Total\AbstractTotal::collect($quote, $shippingAssignment, $total);

        $address = $shippingAssignment->getShipping()->getAddress();
        $method = $shippingAssignment->getShipping()->getMethod();

        $address->setWeight(0);
        $address->setFreeMethodWeight(0);

        $addressWeight = $address->getWeight();
        $freeMethodWeight = $address->getFreeMethodWeight();
        $addressFreeShipping = $address->getFreeShipping();

        $total->setTotalAmount($subject->getCode(), 0);
        $total->setBaseTotalAmount($subject->getCode(), 0);

        if (!count($shippingAssignment->getItems())) {
            return $subject;
        }

        $addressQty = 0;
        foreach ($shippingAssignment->getItems() as $item) {
            /**
             * Skip if this item is virtual
             */
            if ($item->getProduct()->isVirtual()) {
                continue;
            }

            /**
             * Children weight we calculate for parent
             */
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }
                    $addressQty += $child->getTotalQty();

                    if (!$item->getProduct()->getWeightType()) {
                        $itemWeight = $child->getWeight();
                        $itemQty = $child->getTotalQty();
                        $rowWeight = $itemWeight * $itemQty;
                        $addressWeight += $rowWeight;
                        if ($addressFreeShipping || $child->getFreeShipping() === true) {
                            $rowWeight = 0;
                        } elseif (is_numeric($child->getFreeShipping())) {
                            $freeQty = $child->getFreeShipping();
                            if ($itemQty > $freeQty) {
                                $rowWeight = $itemWeight * ($itemQty - $freeQty);
                            } else {
                                $rowWeight = 0;
                            }
                        }
                        $freeMethodWeight += $rowWeight;
                        $item->setRowWeight($rowWeight);
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $itemWeight = $item->getWeight();
                    $rowWeight = $itemWeight * $item->getQty();
                    $addressWeight += $rowWeight;
                    if ($addressFreeShipping || $item->getFreeShipping() === true) {
                        $rowWeight = 0;
                    } elseif (is_numeric($item->getFreeShipping())) {
                        $freeQty = $item->getFreeShipping();
                        if ($item->getQty() > $freeQty) {
                            $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                        } else {
                            $rowWeight = 0;
                        }
                    }
                    $freeMethodWeight += $rowWeight;
                    $item->setRowWeight($rowWeight);
                }
            } else {
                if (!$item->getProduct()->isVirtual()) {
                    $addressQty += $item->getQty();
                }
                $itemWeight = $item->getWeight();
                $rowWeight = $itemWeight * $item->getQty();
                $addressWeight += $rowWeight;
                if ($addressFreeShipping || $item->getFreeShipping() === true) {
                    $rowWeight = 0;
                } elseif (is_numeric($item->getFreeShipping())) {
                    $freeQty = $item->getFreeShipping();
                    if ($item->getQty() > $freeQty) {
                        $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                    } else {
                        $rowWeight = 0;
                    }
                }
                $freeMethodWeight += $rowWeight;
                $item->setRowWeight($rowWeight);
            }
        }

        if (isset($addressQty)) {
            $address->setItemQty($addressQty);
        }

        $address->setWeight($addressWeight);
        $address->setFreeMethodWeight($freeMethodWeight);
        $address->setFreeShipping(
            $this->freeShipping->isFreeShipping($quote, $shippingAssignment->getItems())
        );

        $address->collectShippingRates();

        if ($method) {
            foreach ($address->getAllShippingRates() as $rate) {
                // if ($rate->getCode() == $method) {
                    if ($quote->getData('multiwarehouse_shipping')) {
                        $multiwarehouseShipping = json_decode($quote->getData('multiwarehouse_shipping'), true);
                        $multiAmount = 0;
                        $shippingMethod = "";
                        if (isset($multiwarehouseShipping['warehouse']) && $multiwarehouseShipping['warehouse']) {
                            foreach ($multiwarehouseShipping['warehouse'] as $method) {
                                $multiAmount += $method['amount'];
                                if (isset($method['method_code']) && isset($method['method_code'])) {
                                	$shippingMethod = $method['carrier_code']."_".$method['method_code'];
                                }
                            }
                        } else {
                            $multiAmount = $rate->getPrice();
                        }

                        /** @var \Magento\Store\Api\Data\StoreInterface */
                        $store = $quote->getStore();
                        $amountPrice = $store->getBaseCurrency()
                            ->convert($multiAmount, $store->getCurrentCurrencyCode());
                        $total->setTotalAmount($subject->getCode(), $amountPrice);
                        $total->setBaseTotalAmount($subject->getCode(), $multiAmount);
                        $address->setShippingDescription("MultiWarehouse Shipping");
                        // $address->setShippingMethod($shippingMethod);
                        $total->setBaseShippingAmount($multiAmount);
                        $total->setShippingAmount($amountPrice);
                        $total->setShippingDescription($address->getShippingDescription());
                        break;
                    } else {
                        $store = $quote->getStore();
                        $amountPrice = $this->priceCurrency->convert(
                            $rate->getPrice(),
                            $store
                        );
                        $total->setTotalAmount($subject->getCode(), $amountPrice);
                        $total->setBaseTotalAmount($subject->getCode(), $rate->getPrice());
                        $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                        $address->setShippingDescription(trim($shippingDescription, ' -'));
                        $total->setBaseShippingAmount($rate->getPrice());
                        $total->setShippingAmount($amountPrice);
                        $total->setShippingDescription($address->getShippingDescription());
                        break;
                    }
                // }
            }
        }
        return $subject;
    }
}