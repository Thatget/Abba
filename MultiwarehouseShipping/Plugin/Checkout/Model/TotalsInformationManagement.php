<?php
namespace MW\MultiwarehouseShipping\Plugin\Checkout\Model;

class TotalsInformationManagement extends \Magento\Checkout\Model\TotalsInformationManagement
{
    
    /**
     * {@inheritDoc}
     */
    public function aroundCalculate(
        \Magento\Checkout\Model\TotalsInformationManagement $subject,
        \Closure $process,
        $cartId,
        \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $this->validateQuote($quote);

        $extensionAttribute = $addressInformation->getExtensionAttributes();
        if ($extensionAttribute) {
            $multiwarehouseInfo = $extensionAttribute->getMultiwarehouse();
            if ($multiwarehouseInfo) {
                $quote->setData('multiwarehouse_shipping', $multiwarehouseInfo);
            }
        }

        return $process($cartId, $addressInformation);
    }
}