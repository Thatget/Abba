<?php
/**
 *  Mage-World
 *
 * @category    Mage-World
 * @package     MW
 * @author      Dreyar Developer (dreyar@mage-world.com)
 *
 * @copyright   Copyright (c) 2018 Mage-World (https://www.mage-world.com/)
 */

namespace MW\MultiwarehouseShipping\Rewrite\Purolator\Model\Carrier;

use Purolator\Shipping\Helper\Data;
use Purolator\Shipping\Model\Carrier\Purolator\CostMarkupModifier;
use Purolator\Shipping\Model\Purolator\EstimateService;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Model\ScopeInterface;
use Purolator\Shipping\Model\Purolator\TrackingService;
use Purolator\Shipping\Model\Adminhtml\Source\ShippingServices;

class Purolator extends \Purolator\Shipping\Model\Carrier\Purolator
{
    /** @var int */
    private $markupSpecificSetting = null;

    /** @var CostMarkupModifier */
    private $costMarkupModifier;

    /**
     * @var ShippingServices
     */
    protected $shippingServices;

    /**
     * Purolator constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param EstimateService $purolatorRequest
     * @param \Purolator\Shipping\Model\Purolator\LabelService $purolatorLabelService
     * @param \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService
     * @param \Magento\Directory\Model\RegionFactory $regionInterfaceFactory
     * @param \Magento\Directory\Model\ResourceModel\Region $regionResourceModel
     * @param Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param CostMarkupModifier $costMarkupModifier
     * @param TrackingService $trackingService
     * @param ShippingServices $shippingServices
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Purolator\Shipping\Model\Purolator\EstimateService $purolatorRequest,
        \Purolator\Shipping\Model\Purolator\LabelService $purolatorLabelService,
        \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService,
        \Magento\Directory\Model\RegionFactory $regionInterfaceFactory,
        \Magento\Directory\Model\ResourceModel\Region $regionResourceModel,
        Data $helper,
        \Magento\Framework\Registry $registry,
        CostMarkupModifier $costMarkupModifier,
        TrackingService $trackingService,
        ShippingServices $shippingServices,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $rateResultFactory, $rateMethodFactory, $purolatorRequest, $purolatorLabelService, $purolatorShipmentService, $regionInterfaceFactory, $regionResourceModel, $helper, $registry, $costMarkupModifier, $trackingService, $data);
        $this->costMarkupModifier = $costMarkupModifier;
        $this->shippingServices = $shippingServices;
    }

    /**
     * @param RateRequest $request
     * @return bool|\Magento\Framework\DataObject|\Magento\Shipping\Model\Rate\Result|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->rateResultFactory->create();

        $senderPostCode = $this->_scopeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORE, $request->getStoreId());
        $recevierCity = $request->getDestCity();

        $regId = $request->getDestRegionId();
        $recevierProvince = $this->regionInterfaceFactory->create(['region_id' => $request->getRegionId()]);
        $this->regionResourceModel->load($recevierProvince, $regId, 'region_id');
        $requestResponse = NULL;
        try {
            $requestResponse = $this->purolatorRequest->getRatesFromAddresses(
                $senderPostCode,
                $recevierCity,
                $recevierProvince->getData('code'),
                $request->getDestCountryId(),
                $request->getDestPostcode(),
                $request
            );
        } catch (\Exception $e) {
            //var_dump($e->getMessage());die;
            $this->_logger->error($e->getMessage());
        }

        $allowedMethods = $this->helper->getAllowedMethods();



        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/purolator-3.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_R($requestResponse,true));



        if (!empty($requestResponse->ResponseInformation->Errors)) {
            if (!is_object($requestResponse->ShipmentEstimates)) {
                return $result;
            }
            if (is_array($requestResponse->ShipmentEstimates->ShipmentEstimate)) {
                foreach ($requestResponse->ShipmentEstimates->ShipmentEstimate as $rate) {
                    if (in_array($rate->ServiceID, $allowedMethods) || empty($allowedMethods)) {
                        $this->markupPrice($rate);
                        $result->append($this->createResultMethod((float)$rate->TotalPrice, $rate->ServiceID, $rate->ExpectedDeliveryDate, $rate->EstimatedTransitDays));
                    }
                }
            } else {
                $rate = $requestResponse->ShipmentEstimates->ShipmentEstimate;
                if (in_array($rate->ServiceID, $allowedMethods) || empty($allowedMethods)) {
                    $this->markupPrice($rate);
                    $result->append($this->createResultMethod((float)$rate->TotalPrice, $rate->ServiceID, $rate->ExpectedDeliveryDate, $rate->EstimatedTransitDays));
                }
            }
        }

        return $result;
    }

    /**
     * Shipping cost adjustments based on backend settings
     *
     * @param $rate
     * @return mixed
     */
    private function markupPrice($rate)
    {
        if ($this->markupSpecificSetting == null) {
            $this->markupSpecificSetting = $this->helper->getCostMarkupSpecific();
        }

        if ($this->markupSpecificSetting === 0) {
            return $rate;
        } else {
            return $this->costMarkupModifier->adjustShippingCost($rate);
        }
    }

    /**
     * @param $shippingPrice
     * @param $rateCode
     * @param $expectedDeliveryDate
     * @param $estimatedTransitDays
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createResultMethod($shippingPrice, $rateCode, $expectedDeliveryDate, $estimatedTransitDays)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();
        $title = $rateCode;

        // $groupDelayDays = false;
        $delayDays = false;
        // $delayDays = $senderPostCode = $this->_scopeConfig->getValue('carriers/purolator/delay_day', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $groupName = $this->shippingServices->getGroupFromService($rateCode);
        if ($groupName) {
            $groupDelayDays = $this->getDelayDayByGroup($groupName);
            if ($groupDelayDays) {
                $delayDays = $groupDelayDays;
            }
        }

        if (!empty($expectedDeliveryDate)) {
            if ($delayDays) {
                $expectedDeliveryDate = date('Y-m-d', strtotime($expectedDeliveryDate. ' + '.$delayDays.' days'));
            }
            $title = $expectedDeliveryDate . ' ' . $title;
        }

        if (!empty($estimatedTransitDays)) {
            if ($delayDays) {
                $estimatedTransitDays += $delayDays;
            }
            if ((int)$estimatedTransitDays > 1) {
                $tmp =  __('days');
            } else {
                $tmp = __('day');
            }

            $title = $estimatedTransitDays . ' ' . $tmp . ' ' . $title;
        }

        $method->setCarrier($this->getCarrierCode());
        $method->setCarrierTitle($title);

        $method->setMethod($rateCode);
        $method->setMethodTitle($rateCode);

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        return $method;
    }

    /**
     * @param $groupName
     * @return mixed
     */
    private function getDelayDayByGroup($groupName)
    {
        return $this->_scopeConfig->getValue('carriers/purolator/'.$groupName.'_delay_day', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
