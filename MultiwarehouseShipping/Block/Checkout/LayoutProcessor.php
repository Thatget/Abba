<?php
/**
 * *
 *  Copyright © 2016 MW. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

namespace MW\MultiwarehouseShipping\Block\Checkout;

/**
 * Class LayoutProcessor
 * @package MW\MultiwarehouseShipping\Block\Checkout
 */
class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (true) {
            $shippingConfig = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress'];
            $shippingConfig['component'] = 'MW_MultiwarehouseShipping/js/view/shipping';
        }

        return $jsLayout;
    }
}
