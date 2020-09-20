<?php
namespace MW\MultiwarehouseShipping\Plugin\Quote\Model\Quote;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

class Address extends \Magento\Quote\Model\Quote\Address
{

    /**
     * Quote object
     *
     * @var \Magento\Quote\Model\Quote
     */
    protected $_items;

    /**
     * Quote object
     *
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * Sales Quote address rates
     *
     * @var \Magento\Quote\Model\Quote\Address\Rate
     */
    protected $_rates;

    /**
     * Total models collector
     *
     * @var \Magento\Quote\Model\Quote\Address\Total\Collector
     */
    protected $_totalCollector;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote\Address\ItemFactory
     */
    protected $_addressItemFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory
     */
    protected $_rateCollector;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory
     */
    protected $_rateCollectionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Total\CollectorFactory
     */
    protected $_totalCollectorFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\TotalFactory
     */
    protected $_addressTotalFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateFactory
     * @since 100.2.0
     */
    protected $_addressRateFactory;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Validator
     */
    protected $validator;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateRequestFactory
     */
    protected $_rateRequestFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\CustomAttributeListInterface
     */
    protected $attributeList;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsReader
     */
    protected $totalsReader;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param AddressMetadataInterface $metadataService
     * @param AddressInterfaceFactory $addressDataFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\ItemFactory $addressItemFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Quote\Model\Quote\Address\RateFactory $addressRateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory $rateCollector
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory $rateCollectionFactory
     * @param \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory
     * @param \Magento\Quote\Model\Quote\Address\Total\CollectorFactory $totalCollectorFactory
     * @param \Magento\Quote\Model\Quote\Address\TotalFactory $addressTotalFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory
     * @param \Magento\Quote\Model\Quote\Address\Validator $validator
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Quote\Model\Quote\Address\CustomAttributeListInterface $attributeList
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Quote\Model\Quote\TotalsReader $totalsReader
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json $serializer
     * @param StoreManagerInterface $storeManager
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\ItemFactory $addressItemFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Quote\Model\Quote\Address\RateFactory $addressRateFactory,
        \Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory $rateCollector,
        \Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory $rateCollectionFactory,
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Quote\Model\Quote\Address\Total\CollectorFactory $totalCollectorFactory,
        \Magento\Quote\Model\Quote\Address\TotalFactory $addressTotalFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory,
        \Magento\Quote\Model\Quote\Address\Validator $validator,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Quote\Model\Quote\Address\CustomAttributeListInterface $attributeList,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\TotalsReader $totalsReader,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null,
        StoreManagerInterface $storeManager = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $directoryData,
            $eavConfig,
            $addressConfig,
            $regionFactory,
            $countryFactory,
            $metadataService,
            $addressDataFactory,
            $regionDataFactory,
            $dataObjectHelper,
            $scopeConfig,
            $addressItemFactory,
            $itemCollectionFactory,
            $addressRateFactory,
            $rateCollector,
            $rateCollectionFactory,
            $rateRequestFactory,
            $totalCollectorFactory,
            $addressTotalFactory,
            $objectCopyService,
            $carrierFactory,
            $validator,
            $addressMapper,
            $attributeList,
            $totalsCollector,
            $totalsReader,
            $resource,
            $resourceCollection,
            $data,
            $serializer,
            $storeManager
        );

        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    public function aroundRequestShippingRates(
        \Magento\Quote\Model\Quote\Address $subject,
        \Closure $process,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item = null
    )
    {
        if (false) {
            return $process($item);
        }

        /** @var $request \Magento\Quote\Model\Quote\Address\RateRequest */
        $request = $this->_rateRequestFactory->create();
        $request->setAllItems($item ? [$item] : $subject->getAllItems());
        $request->setDestCountryId($subject->getCountryId());
        $request->setDestRegionId($subject->getRegionId());
        $request->setDestRegionCode($subject->getRegionCode());
        $request->setDestStreet($subject->getStreetFull());
        $request->setDestCity($subject->getCity());
        $request->setDestPostcode($subject->getPostcode());
        $request->setPackageValue($item ? $item->getBaseRowTotal() : $subject->getBaseSubtotal());
        $packageWithDiscount = $item ? $item->getBaseRowTotal() -
            $item->getBaseDiscountAmount() : $subject->getBaseSubtotalWithDiscount();
        $request->setPackageValueWithDiscount($packageWithDiscount);
        $request->setPackageWeight($item ? $item->getRowWeight() : $subject->getWeight());
        $request->setPackageQty($item ? $item->getQty() : $subject->getItemQty());

        /**
         * Need for shipping methods that use insurance based on price of physical products
         */
        $packagePhysicalValue = $item ? $item->getBaseRowTotal() : $subject->getBaseSubtotal() -
            $subject->getBaseVirtualAmount();
        $request->setPackagePhysicalValue($packagePhysicalValue);

        $request->setFreeMethodWeight($item ? 0 : $subject->getFreeMethodWeight());

        /**
         * Store and website identifiers specified from StoreManager
         */
        $request->setStoreId($this->storeManager->getStore()->getId());
        $request->setWebsiteId($this->storeManager->getWebsite()->getId());
        $request->setFreeShipping($subject->getFreeShipping());
        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($this->storeManager->getStore()->getBaseCurrency());
        $request->setPackageCurrency($this->storeManager->getStore()->getCurrentCurrency());
        $request->setLimitCarrier($subject->getLimitCarrier());
        $baseSubtotalInclTax = $subject->getBaseSubtotalTotalInclTax();
        $request->setBaseSubtotalInclTax($baseSubtotalInclTax);

        $result = $this->_rateCollector->create()->collectRates($request)->getResult();

        $found = false;
        if ($result) {
            $shippingRates = $result->getAllRates();

            foreach ($shippingRates as $shippingRate) {
                $rate = $this->_addressRateFactory->create()->importShippingRate($shippingRate);
                if (!$item) {
                    $subject->addShippingRate($rate);
                }
                if ($subject->getShippingMethod() == $rate->getCode()) {
                    $quote = $subject->getQuote();
                    if ($quote->getData('multiwarehouse_shipping')) {
                        if ($item) {
                            $item->setBaseShippingAmount($rate->getPrice());
                        } else {
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
                            $store = $this->storeManager->getStore();
                            $amountPrice = $store->getBaseCurrency()
                                ->convert($multiAmount, $store->getCurrentCurrencyCode());
                            $subject->setBaseShippingAmount($multiAmount);
                            $subject->setShippingAmount($amountPrice);
                            // $subject->setShippingMethod($shippingMethod);
                        }

                        $found = true;
                    } else {
                        if ($item) {
                            $item->setBaseShippingAmount($rate->getPrice());
                        } else {

                            /** @var \Magento\Store\Api\Data\StoreInterface */
                            $store = $this->storeManager->getStore();
                            $amountPrice = $store->getBaseCurrency()
                                ->convert($rate->getPrice(), $store->getCurrentCurrencyCode());
                            $subject->setBaseShippingAmount($rate->getPrice());
                            $subject->setShippingAmount($amountPrice);
                        }

                        $found = true;
                    }
                }
            }
        }

        return $found;
    }

    public function xlog($message = 'null')
    {
        $log = print_r($message, true);
        $logger = new \Zend\Log\Logger;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/dreyarr.log');
        $logger->addWriter($writer);
        $logger->info($log);
    }
}