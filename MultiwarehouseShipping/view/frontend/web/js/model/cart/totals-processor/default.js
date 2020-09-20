/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
	'ko',
    'underscore',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Customer/js/customer-data'
], function (ko, _, resourceUrlManager, quote, storage, totalsService, errorProcessor, cartCache, customerData) {
    'use strict';

    /**
     * Load data from server.
     *
     * @param {Object} address
     */
    var loadFromServer = function (address) {
        var serviceUrl,
            payload;

        // Start loader for totals block
        totalsService.isLoading(true);
        serviceUrl = resourceUrlManager.getUrlForTotalsEstimationForNewAddress(quote);
        payload = {
            addressInformation: {
                address: _.pick(address, cartCache.requiredFields)
            }
        };

        if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
            payload.addressInformation['shipping_method_code'] = quote.shippingMethod()['method_code'];
            payload.addressInformation['shipping_carrier_code'] = quote.shippingMethod()['carrier_code'];
        }

        storage.post(
            serviceUrl, JSON.stringify(payload), false
        ).done(function (result) {
            var data = {
                totals: result,
                address: address,
                cartVersion: customerData.get('cart')()['data_id'],
                shippingMethodCode: null,
                shippingCarrierCode: null
            };

            if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
                data.shippingMethodCode = quote.shippingMethod()['method_code'];
                data.shippingCarrierCode = quote.shippingMethod()['carrier_code'];
            }

            quote.setTotals(result);
            cartCache.set('cart-data', data);
        }).fail(function (response) {
            errorProcessor.process(response);
        }).always(function () {
            // Stop loader for totals block
            totalsService.isLoading(false);
        });
    };

    var loadFromServerMultiWarehouse = function (address, warehouseData) {
        var serviceUrl,
            payload;

        // Start loader for totals block
        totalsService.isLoading(true);
        serviceUrl = resourceUrlManager.getUrlForTotalsEstimationForNewAddress(quote);
        payload = {
            addressInformation: {
                address: _.pick(address, cartCache.requiredFields)
            }
        };

        if (quote.shippingMethod() && quote.shippingMethod()['warehouse'] && quote.shippingMethod()['warehouse'].length > 0) {
            payload.addressInformation['extension_attributes'] = { multiwarehouse: JSON.stringify(warehouseData) };
        }

        if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
            payload.addressInformation['shipping_method_code'] = quote.shippingMethod()['method_code'];
            payload.addressInformation['shipping_carrier_code'] = quote.shippingMethod()['carrier_code'];
        }

        storage.post(
            serviceUrl, JSON.stringify(payload), false
        ).done(function (result) {
            var data = {
                totals: result,
                address: address,
                cartVersion: customerData.get('cart')()['data_id'],
                shippingMethodCode: null,
                shippingCarrierCode: null
            };

            if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
                data.shippingMethodCode = quote.shippingMethod()['method_code'];
                data.shippingCarrierCode = quote.shippingMethod()['carrier_code'];
            }

            quote.setTotals(result);
            cartCache.set('cart-data', data);
        }).fail(function (response) {
            errorProcessor.process(response);
        }).always(function () {
            // Stop loader for totals block
            totalsService.isLoading(false);
        });
    };

    return {
        multiwarehouseShipping: ko.observableArray([]),

        /**
         * Array of required address fields.
         * @property {Array.String} requiredFields
         * @deprecated Use cart cache.
         */
        requiredFields: cartCache.requiredFields,

        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        estimateTotals: function (address) {
            var cacheable = false;
            var data = {
                shippingMethodCode: null,
                shippingCarrierCode: null
            };

            if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
                data.shippingMethodCode = quote.shippingMethod()['method_code'];
                data.shippingCarrierCode = quote.shippingMethod()['carrier_code'];
            }

            if (cacheable && !cartCache.isChanged('cartVersion', customerData.get('cart')()['data_id']) &&
                !cartCache.isChanged('shippingMethodCode', data.shippingMethodCode) &&
                !cartCache.isChanged('shippingCarrierCode', data.shippingCarrierCode) &&
                !cartCache.isChanged('address', address) &&
                cartCache.get('totals') &&
                !cartCache.isChanged('subtotal', parseFloat(quote.totals().subtotal))
            ) {
                quote.setTotals(cartCache.get('totals'));
            } else {
                loadFromServerMultiWarehouse(address, this.multiwarehouseShipping());
            }
        }
    };
});
