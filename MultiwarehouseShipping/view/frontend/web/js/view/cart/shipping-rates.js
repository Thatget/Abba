/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'underscore',
    'jquery',
    'Magento_Checkout/js/view/cart/shipping-rates',
    'MW_MultiwarehouseShipping/js/model/multiwarehouse-shipping',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function (ko, _, $, CheckoutShippingRates, multiwarehouse, selectShippingMethodAction, quote, checkoutData, totalProcessor) {
    'use strict';

    return CheckoutShippingRates.extend({
        defaults: {
            template: 'MW_MultiwarehouseShipping/cart/shipping-rates'
        },
        warehouses: multiwarehouse.getWarehouses(),
        selectedShippingMethod: ko.computed(function () {
            return quote.shippingMethod() ?
                quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] :
                null;
        }),

        /**
         * Set shipping method.
         * @param {String} methodData
         * @returns bool
         */
        selectShippingMethod: function (methodData) {
            if (!this.validateAllWarehouseBeSelected()) {
                return true;
            }

            var multiMethodData = { warehouse: [] }, 
                methodWh = [],
                method_code = '', warehouseCode = '';
            $('#co-shipping-method-form input[type=radio]').each(function (i) {
                if ($(this).is(':checked')) {
                    method_code = $(this).attr('method-wh');
                    warehouseCode = $(this).attr('name');

                    multiMethodData.warehouse.push(
                        {
                            warehouse_code: warehouseCode,
                            method_code: method_code,
                            carrier_code: $(this).attr('carrier-code'),
                            method_title: $(this).attr('method-title'),
                            carrier_title: $(this).attr('carrier-title'),
                            amount: $(this).attr('price')
                        }
                    );
                }
            });

            totalProcessor.multiwarehouseShipping(multiMethodData);
            selectShippingMethodAction(multiMethodData);
            checkoutData.setSelectedShippingRate(methodData['carrier_code'] + '_' + methodData['method_code']);

            return true;
        },

        validateAllWarehouseBeSelected: function() {
            var warehouses = this.warehouses();
            var count = 0;

            for (var counter in warehouses) {
                $('#co-shipping-method-form input[type=radio]').each(function (i) {
                    if ($(this).is(':checked') && $(this).attr('name') == warehouses[counter].code) {
                        count++;
                        return;
                    }
                });
            }

            if (count != warehouses.length) {
                return false;
            }

            return true;
        }
    });
});
