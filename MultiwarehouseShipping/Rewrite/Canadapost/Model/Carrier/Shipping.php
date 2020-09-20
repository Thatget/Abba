<?php

namespace MW\MultiwarehouseShipping\Rewrite\Canadapost\Model\Carrier;

class Shipping extends \Meetanshi\Canadapost\Model\Carrier\Shipping
{
	public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if ($this->getConfigFlag('for_admin')) {
            if ($this->state->getAreaCode() != 'adminhtml') {
                return false;
            }
        }
        $this->rawRequest = $request;
        $this->rawRequest->setValueWithDiscount($request->getPackageValueWithDiscount());

        $rateAdapter = $this->rating;
        $result = $this->rateResultFactory->create();
        $allowedMethods = $this->getAllowedCPMethods();

        $parcel = ['weight' => $this->helper->convertWeight($request['package_weight'], $this->getConfigData('weight'))];

        if ($parcel['weight'] > 99.999) {
            throw new \Magento\Framework\Exception\LocalizedException('99.99 KG is allowed per pacakge.');
        }

        $destination = ['postal_code' => $this->helper->formatAreaCode($request['dest_postcode']), 'dest_country_id' => $request['dest_country_id']];
        try {
        	if ($request->getOrigPostcode()) {
        		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        		$multiWarehouseRateAdapter = $objectManager->create("MW\MultiwarehouseShipping\Rewrite\Canadapost\Model\Adapter\Rating");
				$response = $multiWarehouseRateAdapter->getMultiWarehouseRates($parcel, $destination, $request->getOrigPostcode());
        	} else {
        		$response = $rateAdapter->getRates($parcel, $destination);
        	}
        } catch (\Exception $e) {
        }

        if (isset($response->{'price-quotes'}->{'price-quote'}) && is_array($response->{'price-quotes'}->{'price-quote'})) {
            foreach ($response->{'price-quotes'}->{'price-quote'} as $estimate) {
                if (in_array($estimate->{'service-code'}, $allowedMethods)) {
                    $methodCode = $estimate->{'service-code'};

                    $methodName = $estimate->{'service-name'};
                    $methodDescription = '';

                    if ($this->getConfigData('estimate_delivery') && $estimate->{'service-standard'} && isset($estimate->{'service-standard'}->{'expected-delivery-date'})) {
                        $methodName .= ' - Estimate Delivery ' . $estimate->{'service-standard'}->{'expected-delivery-date'};
                    }

                    $rate = $this->rateMethodFactory->create();
                    $rate->setCarrier($this->_code);
                    $rate->setCarrierTitle($this->getConfigData('title'));
                    $rate->setMethod($methodCode);
                    $rate->setMethodTitle($methodName);
                    $rate->setMethodDescription($methodDescription);
                    $rate->setCost($this->getFormattedPrice($estimate->{'price-details'}->{'due'}, $methodCode));
                    $rate->setPrice($this->getFormattedPrice($estimate->{'price-details'}->{'due'}, $methodCode));
                    $result->append($rate);
                }
            }
        }
        if (isset($response->{'messages'}->{'message'}) && ($this->getConfigData('show_method') == 1)) {
            foreach ($response->{'messages'}->{'message'} as $message) {
                $error_message = $message->description;
            }
            $error = $this->rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($error_message);
            $result->append($error);
            return $error;
        }
        if (($this->getConfigData('show_method') == 1) && empty($response)) {
            $error = $this->rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
            return $error;
        }
        return $result;
    }
}