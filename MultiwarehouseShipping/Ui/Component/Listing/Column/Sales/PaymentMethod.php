<?php

namespace MW\MultiwarehouseShipping\Ui\Component\Listing\Column\Sales;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PaymentMethod extends Column
{

    protected $orderRepository;
    protected $config;
  

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
        \Magento\Framework\App\Config\ScopeConfigInterface $scope,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->config = $scope;
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

            $methodList = $this->config->getValue('payment');

            foreach ($dataSource['data']['items'] as & $item) {
                if(isset($methodList[$item['payment_method']])){
                    if($item['payment_method'] == 'banktransfer'){
                        $item[$this->getData('name')] = '<strong style="color: #F7BC0E;">'.$methodList[$item['payment_method']]['title'].'</strong>';
                    }else{
                         $item[$this->getData('name')] = $methodList[$item['payment_method']]['title'];
                    }
                }
            }
        }

        return $dataSource;
    }
}
