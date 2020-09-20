define([
    'jquery',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/quote'
], function ($, totalProcessor, priceUtils, quote) {
    'use strict';

    return function (Shipping) {
        return Shipping.extend({
            getShippingMethodTitle: function () {
                if (!totalProcessor.multiwarehouseShipping().warehouse) {
                    return this._super();
                }

                var warehouseData = totalProcessor.multiwarehouseShipping().warehouse;

                var shippingMethod = [];

                warehouseData.forEach(function (item) {
                    var text = item.warehouse_code + " - " + item.method_title + " - " + priceUtils.formatPrice(item.amount, quote.getPriceFormat());
                    shippingMethod.push(text);
                });

                return "\n" + shippingMethod.join("\n");
            }
        });
    };
});