define([
    'jquery',
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/quote'
], function ($, shippingSaveProcessor, priceUtils, quote) {
    'use strict';

    return function (Shipping) {
        return Shipping.extend({
            getShippingMethodTitle: function () {
                if (!shippingSaveProcessor.multiwarehouseShipping.warehouse) {
                    return this._super();
                }

                var warehouseData = shippingSaveProcessor.multiwarehouseShipping.warehouse;

                var shippingMethod = [];

                warehouseData.forEach(function (item) {
                    var text = item.warehouse_code + " - " + item.method_title + " - " + priceUtils.formatPrice(item.amount, quote.getPriceFormat());
                    shippingMethod.push(text);
                });

                return shippingMethod.join("\n");
            }
        });
    };
});