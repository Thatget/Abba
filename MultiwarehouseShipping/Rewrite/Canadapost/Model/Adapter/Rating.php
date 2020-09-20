<?php

namespace MW\MultiwarehouseShipping\Rewrite\Canadapost\Model\Adapter;

class Rating extends \Meetanshi\Canadapost\Model\Adapter\Rating
{
	public function getMultiWarehouseRates($parcel, $destination, $warehousePostcode)
    {
        $request = ['get-rates-request' => ['locale' => $this->getConfigData('locale'), 'mailing-scenario' => ['parcel-characteristics' => ['weight' => $parcel['weight']], 'origin-postal-code' => $this->helper->formatAreaCode($warehousePostcode),]]];

        if ($this->getConfigData('rate_type') != 'counter') {
            $request['get-rates-request']['mailing-scenario']['quote-type'] = $this->getConfigData('rate_type');
            $request['get-rates-request']['mailing-scenario']['customer-number'] = $this->getConfigData('client_number');
        } else {
            $request['get-rates-request']['mailing-scenario']['quote-type'] = $this->getConfigData('rate_type');
        }

        $delivery = $this->getConfigData('delivery_offset');

        if (!empty($delivery) && $delivery != '') {
            $date = strtotime(date('Y-m-d'));
            $expected_date = date('Y-m-d', strtotime('+' . (string)$delivery . ' days', $date));
            $request['get-rates-request']['mailing-scenario']['expected-mailing-date'] = (string)$expected_date;
        }

        if ($destination['dest_country_id'] == 'CA') {
            $request['get-rates-request']['mailing-scenario']['destination']['domestic']['postal-code'] = $destination['postal_code'];
        } elseif ($destination['dest_country_id'] == 'US') {
            $request['get-rates-request']['mailing-scenario']['destination']['united-states']['zip-code'] = $destination['postal_code'];
        } else {
            $request['get-rates-request']['mailing-scenario']['destination']['international']['country-code'] = $destination['dest_country_id'];
        }

        $client = $this->createSOAPClient('rating');
        try {
            $result = $client->__soapCall('GetRates', $request, null);
            return $result;
        } catch (\Exception $e) {
        }
    }
}