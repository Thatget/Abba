<?php

namespace MW\MultiwarehouseShipping\Ui\Component\Listing\Column\Sales;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ShippingInformation extends Column
{

    protected $orderRepository;

    protected $pricingHelper;

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
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->pricingHelper = $pricingHelper;
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
                $warehouseHtml = "<strong>".$item[$this->getData('name')]."</strong>";
                if (isset($item['entity_id'])) {
                    $order = $this->orderRepository->get($item['entity_id']);
                    $multiWarehouseShipping = $order->getData('multiwarehouse_shipping');
                    if ($multiWarehouseShipping) {
                        $warehouseHtml = "";
                        $multiWarehouseShippingArray = json_decode($multiWarehouseShipping, true);
                        if (isset($multiWarehouseShippingArray['warehouse'])) {
                            $warehouses = $multiWarehouseShippingArray['warehouse'];
                            if (count($warehouses)) {
                                foreach ($warehouses as $warehouse) {
                                    
                                    if($warehouse['method_title'] == "PurolatorExpress"){
                                        $methodTitleText = "<strong style='color: red;'>".$warehouse['method_title']."</strong>";
                                    }else if($warehouse['method_title'] == "UPS Standard"){
                                        $methodTitleText = "<strong style='color: #bd6711;font-weight:800'>".$warehouse['method_title']."</strong>";
                                    }else{
                                       $methodTitleText = "<strong>".$warehouse['method_title']."</strong>"; 
                                    }

                                    if (isset($warehouse['carrier_title'])) {
                                       // $methodTitleText .= "-".$warehouse['carrier_title'];
                                    }
                                    
                                    $warehouseCode = $warehouse['warehouse_code'];

                                    if( $warehouseCode == 'Richmond Hill'){

                                        $warehouseCode = "<strong style='color: #ff0099;'>".$warehouseCode ."</strong>";
                                    }else if( $warehouseCode == 'chabanel'){

                                        $warehouseCode = "<strong style='color: #2ceb39;'>".$warehouseCode ."</strong>";
                                    }

                                    $warehouseHtml .= (($warehouseHtml?"<br>":"").$warehouseCode."-".$methodTitleText."-".$this->pricingHelper->currency($warehouse['amount'], true, false));
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
