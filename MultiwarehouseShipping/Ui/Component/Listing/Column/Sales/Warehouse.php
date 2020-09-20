<?php

namespace MW\MultiwarehouseShipping\Ui\Component\Listing\Column\Sales;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Warehouse extends Column
{

    protected $orderRepository;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $warehouseHtml = "";
                if (isset($item['entity_id'])) {
                    $order = $this->orderRepository->get($item['entity_id']);
                    $multiWarehouseShipping = $order->getData('multiwarehouse_shipping');
                    if ($multiWarehouseShipping) {
                        $multiWarehouseShippingArray = json_decode($multiWarehouseShipping, true);
                        if (isset($multiWarehouseShippingArray['warehouse'])) {
                            $warehouses = $multiWarehouseShippingArray['warehouse'];
                            if (count($warehouses)) {
                                foreach ($warehouses as $warehouse) {                                    
                                    $warehouseHtml .= ($warehouse['warehouse_code']."<br>");
                                }
                            }
                        }
                    }
                }
                $item[$this->getData('name')] = $warehouseHtml;
            }
        }

        return $dataSource;
    }
}
