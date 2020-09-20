<?php

namespace MW\MultiwarehouseShipping\Plugin\Purolator\Model\Purolator;

class EstimateService
{
    public function aroundGetRatesFromAddresses(\Purolator\Shipping\Model\Purolator\EstimateService $subject, callable $proceed, $senderPostCode, $recevierCity, $recevierProvince, $recevierCountry, $recevierPostCode, $rateRequest)
    {
        if ($rateRequest->getOrigPostcode()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $estimateService = $objectManager->create("MW\MultiwarehouseShipping\Rewrite\Purolator\Model\Purolator\EstimateService");
            $requestResponse = $estimateService->getRatesFromAddressesMultiWarehouse($rateRequest->getOrigPostcode(), $recevierCity, $recevierProvince, $recevierCountry, $recevierPostCode, $rateRequest);
            \Zend_Debug::dump($requestResponse);die;
            return $requestResponse;
        }

        return $proceed($senderPostCode, $recevierCity, $recevierProvince, $recevierCountry, $recevierPostCode, $rateRequest);
    }
}