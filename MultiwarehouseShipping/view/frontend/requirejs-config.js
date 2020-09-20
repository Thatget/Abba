var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/shipping-rate-processor/customer-address':'MW_MultiwarehouseShipping/js/model/shipping-rate-processor/customer-address',
            'Magento_Checkout/js/model/shipping-rate-processor/new-address':'MW_MultiwarehouseShipping/js/model/shipping-rate-processor/new-address',
            'Magento_Checkout/js/model/shipping-save-processor/default':'MW_MultiwarehouseShipping/js/model/shipping-save-processor/default',
            'Magento_Checkout/js/model/cart/totals-processor/default':'MW_MultiwarehouseShipping/js/model/cart/totals-processor/default',
            updateCartMultiwarehouse: 'MW_MultiwarehouseShipping/js/cart/update-cart'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/summary/shipping': {
                'MW_MultiwarehouseShipping/js/view/summary/shipping-mixin': true
            },
            'Magento_Tax/js/view/checkout/cart/totals/shipping': {
                'MW_MultiwarehouseShipping/js/view/checkout/cart/totals/shipping-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'MW_MultiwarehouseShipping/js/view/shipping-information-mixin': true
            }
        }
    }
};