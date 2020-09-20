<?php

namespace MW\MultiwarehouseShipping\Model\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\Config;

class Configurable extends \Magento\ConfigurableProduct\Model\Product\Type\Configurable
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $cache;

    /**
     * @var Config
     */
    private $catalogConfig;

    /**
     * Product factory
     *
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @codingStandardsIgnoreStart/End
     *
     * @param \Magento\Catalog\Model\Product\Option $catalogProductOption
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory $typeConfigurableFactory
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavAttributeFactory
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory $configurableAttributeFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param ProductInterfaceFactory $productFactory
     * @param SalableProcessor $salableProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory $typeConfigurableFactory,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavAttributeFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory $configurableAttributeFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Framework\Cache\FrontendInterface $cache = null,
        \Magento\Customer\Model\Session $customerSession = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        ProductInterfaceFactory $productFactory = null,
        SalableProcessor $salableProcessor = null
    ) {
        $this->cache = $cache;
        $this->customerSession = $customerSession;
        $this->productFactory = $productFactory ?: ObjectManager::getInstance()
            ->get(ProductInterfaceFactory::class);
        parent::__construct(
            $catalogProductOption,
            $eavConfig,
            $catalogProductType,
            $eventManager,
            $fileStorageDb,
            $filesystem,
            $coreRegistry,
            $logger,
            $productRepository,
            $typeConfigurableFactory,
            $eavAttributeFactory,
            $configurableAttributeFactory,
            $productCollectionFactory,
            $attributeCollectionFactory,
            $catalogProductTypeConfigurable,
            $scopeConfig,
            $extensionAttributesJoinProcessor,
            $cache,
            $customerSession,
            $serializer,
            $productFactory,
            $salableProcessor
        );
    }

    /**
     * Prepare product and its configuration to be added to some products list.
     * Perform standard preparation process and then add Configurable specific options.
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param string $processMode
     * @return \Magento\Framework\Phrase|array|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _prepareProduct(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        $attributes = $buyRequest->getSuperAttribute();
        if ($attributes || !$this->_isStrictProcessMode($processMode)) {
            if (!$this->_isStrictProcessMode($processMode)) {
                if (is_array($attributes)) {
                    foreach ($attributes as $key => $val) {
                        if (empty($val)) {
                            unset($attributes[$key]);
                        }
                    }
                } else {
                    $attributes = [];
                }
            }

            $result = \Magento\Catalog\Model\Product\Type\AbstractType::_prepareProduct($buyRequest, $product, $processMode);
            if (is_array($result)) {
                //TODO: MAGETWO-23739 get id from _POST and retrieve product from repository immediately.

                /**
                 * $attributes = array($attributeId=>$attributeValue)
                 */
                $subProduct = true;
                if ($this->_isStrictProcessMode($processMode)) {
                    foreach ($this->getConfigurableAttributes($product) as $attributeItem) {
                        /* @var $attributeItem \Magento\Framework\DataObject */
                        $attrId = $attributeItem->getData('attribute_id');
                        if (!isset($attributes[$attrId]) || empty($attributes[$attrId])) {
                            $subProduct = null;
                            break;
                        }
                    }
                }
                if ($subProduct) {
                    $subProduct = $this->getProductByAttributes($attributes, $product);
                    if ($subProduct) {
                        $subProduct = clone $this->getProductByAttributes($attributes, $product);
                    }
                }

                if ($subProduct) {
                    $subProductLinkFieldId = $subProduct->getId();
                    $product->addCustomOption('attributes', $this->serializer->serialize($attributes));
                    $product->addCustomOption('product_qty_' . $subProductLinkFieldId, 1, $subProduct);
                    $product->addCustomOption('simple_product', $subProductLinkFieldId, $subProduct);

                    $_result = $subProduct->getTypeInstance()->processConfiguration(
                        $buyRequest,
                        $subProduct,
                        $processMode
                    );
                    if (is_string($_result) && !is_array($_result)) {
                        return $_result;
                    }

                    if (!isset($_result[0])) {
                        return __('You can\'t add the item to shopping cart.')->render();
                    }

                    /**
                     * Adding parent product custom options to child product
                     * to be sure that it will be unique as its parent
                     */
                    if ($optionIds = $product->getCustomOption('option_ids')) {
                        $optionIds = explode(',', $optionIds->getValue());
                        foreach ($optionIds as $optionId) {
                            if ($option = $product->getCustomOption('option_' . $optionId)) {
                                $_result[0]->addCustomOption('option_' . $optionId, $option->getValue());
                            }
                        }
                    }

                    $productLinkFieldId = $product->getId();
                    $_result[0]->setParentProductId($productLinkFieldId)
                        ->addCustomOption('parent_product_id', $productLinkFieldId);
                    if ($this->_isStrictProcessMode($processMode)) {
                        $_result[0]->setCartQty(1);
                    }
                    $result[] = $_result[0];
                    return $result;
                } else {
                    if (!$this->_isStrictProcessMode($processMode)) {
                        return $result;
                    }
                }
            }
        }

        return $this->getSpecifyOptionMessage()->render();
    }

    /**
     * Returns array of sub-products for specified configurable product include out of stock
     *
     * $requiredAttributeIds - one dimensional array, if provided
     *
     * Result array contains all children for specified configurable product
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @param  array $requiredAttributeIds
     * @return ProductInterface[]
     */
    public function getAllUsedProducts($product, $requiredAttributeIds = null)
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        $metadata = $this->getMetadataPool()->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $keyParts = [
            __METHOD__,
            $product->getData($metadata->getLinkField()),
            $product->getStoreId(),
            $this->getCustomerSession()->getCustomerGroupId()
        ];
        if ($requiredAttributeIds !== null) {
            sort($requiredAttributeIds);
            $keyParts[] = implode('', $requiredAttributeIds);
        }
        $cacheKey = $this->getUsedProductsCacheKey($keyParts);
        return $this->loadAllUsedProducts($product, $cacheKey);
    }

    /**
     * Load collection on sub-products for specified configurable product
     *
     * Load collection of sub-products, apply result to specified configurable product and store result to cache
     * Please note $salableOnly parameter is used for backwards compatibility because of deprecated method
     * getSalableUsedProducts
     * Number of loaded sub-products depends on $salableOnly parameter
     * $salableOnly = true - result array contains only salable sub-products
     * $salableOnly = false - result array contains all sub-products
     * $cacheKey - allow store result data in different cache records
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $cacheKey
     * @param bool $salableOnly
     * @return ProductInterface[]
     */
    private function loadAllUsedProducts(\Magento\Catalog\Model\Product $product, $cacheKey, $salableOnly = false)
    {
        $dataFieldName = '_cache_instance_all_products';
        if (!$product->hasData($dataFieldName)) {
            $usedProducts = $this->readUsedProductsCacheData($cacheKey);
            if ($usedProducts === null) {
                $collection = $this->getConfiguredUsedProductCollection($product, true);
                if ($salableOnly) {
                    $collection = $this->salableProcessor->process($collection);
                }
                $usedProducts = array_values($collection->getItems());
                $this->saveUsedProductsCacheData($product, $usedProducts, $cacheKey);
            }
            $product->setData($dataFieldName, $usedProducts);
        }

        return $product->getData($dataFieldName);
    }

    /**
     * Create string key based on $keyParts
     *
     * $keyParts - one dimensional array of strings
     *
     * @param array $keyParts
     * @return string
     */
    private function getUsedProductsCacheKey($keyParts)
    {
        return sha1(implode('_', $keyParts));
    }

    /**
     * Read used products data from cache
     *
     * Looking for cache record stored under provided $cacheKey
     * In case data exists turns it into array of products
     *
     * @param string $cacheKey
     * @return ProductInterface[]|null
     */
    private function readUsedProductsCacheData($cacheKey)
    {
        $usedProducts = null;
        $data = $this->getCache()->load($cacheKey);
        if (!$data) {
            return $usedProducts;
        }
        $data = $this->serializer->unserialize($data);
        if (!empty($data)) {
            $usedProducts = [];
            foreach ($data as $item) {
                $productItem = $this->productFactory->create();
                $productItem->setData($item);
                $usedProducts[] = $productItem;
            }
        }

        return $usedProducts;
    }

    /**
     * Prepare collection for retrieving sub-products of specified configurable product
     *
     * Retrieve related products collection with additional configuration
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $skipStockFilter
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection
     */
    private function getConfiguredUsedProductCollection(
        \Magento\Catalog\Model\Product $product,
        $skipStockFilter = true
    ) {
        $collection = $this->getUsedProductCollection($product);

        if ($skipStockFilter) {
            $collection->setFlag('has_stock_status_filter', true);
        }

        $collection
            ->addAttributeToSelect($this->getAttributesForCollection($product))
            ->addFilterByRequiredOptions()
            ->setStoreId($product->getStoreId());

        $collection->addMediaGalleryData();
        $collection->addTierPriceData();

        return $collection;
    }

    /**
     * Save $subProducts to cache record identified with provided $cacheKey
     *
     * Cached data will be tagged with combined list of product tags and data specific tags i.e. 'price' etc.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param ProductInterface[] $subProducts
     * @param string $cacheKey
     * @return bool
     */
    private function saveUsedProductsCacheData(\Magento\Catalog\Model\Product $product, array $subProducts, $cacheKey)
    {
        $metadata = $this->getMetadataPool()->getMetadata(ProductInterface::class);
        return $this->getCache()->save(
            $this->serializer->serialize(array_map(
                function ($item) {
                    return $item->getData();
                },
                $subProducts
            )),
            $cacheKey,
            array_merge(
                $product->getIdentities(),
                [
                    \Magento\Catalog\Model\Category::CACHE_TAG,
                    \Magento\Catalog\Model\Product::CACHE_TAG,
                    'price',
                    self::TYPE_CODE . '_' . $product->getData($metadata->getLinkField())
                ]
            )
        );
    }

    /**
     * Get MetadataPool instance
     * @return MetadataPool
     */
    private function getMetadataPool()
    {
        if (!$this->metadataPool) {
            $this->metadataPool = ObjectManager::getInstance()->get(MetadataPool::class);
        }
        return $this->metadataPool;
    }

    /**
     * @deprecated 100.1.1
     * @return \Magento\Customer\Model\Session
     */
    private function getCustomerSession()
    {
        if (null === $this->customerSession) {
            $this->customerSession = ObjectManager::getInstance()->get(\Magento\Customer\Model\Session::class);
        }
        return $this->customerSession;
    }

    /**
     * @deprecated 100.1.1
     * @return \Magento\Framework\Cache\FrontendInterface
     */
    private function getCache()
    {
        if (null === $this->cache) {
            $this->cache = ObjectManager::getInstance()->get(\Magento\Framework\Cache\FrontendInterface::class);
        }
        return $this->cache;
    }

    /**
     * @return array
     */
    private function getAttributesForCollection(\Magento\Catalog\Model\Product $product)
    {
        $productAttributes = $this->getCatalogConfig()->getProductAttributes();

        $requiredAttributes = [
            'name',
            'price',
            'weight',
            'image',
            'thumbnail',
            'status',
            'visibility',
            'media_gallery'
        ];

        $usedAttributes = array_map(
            function($attr) {
                return $attr->getAttributeCode();
            },
            $this->getUsedProductAttributes($product)
        );

        return array_unique(array_merge($productAttributes, $requiredAttributes, $usedAttributes));
    }

     /**
     * Get Config instance
     * @return Config
     * @deprecated 100.1.0
     */
    private function getCatalogConfig()
    {
        if (!$this->catalogConfig) {
            $this->catalogConfig = ObjectManager::getInstance()->get(Config::class);
        }
        return $this->catalogConfig;
    }
}