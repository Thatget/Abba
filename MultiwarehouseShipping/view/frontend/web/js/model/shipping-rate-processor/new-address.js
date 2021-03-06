/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/error-processor',
    'MW_MultiwarehouseShipping/js/model/multiwarehouse-shipping'
], function (resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor, multiwarehouse) {
    'use strict';

    return {
        cartWarehouses: [],

        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function (address) {
            var cache, serviceUrl, payload;
            var owner = this;
            var cacheable = false;

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getCacheKey());
            serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
            payload = JSON.stringify({
                    address: {
                        'street': address.street,
                        'city': address.city,
                        'region_id': address.regionId,
                        'region': address.region,
                        'country_id': address.countryId,
                        'postcode': address.postcode,
                        'email': address.email,
                        'customer_id': address.customerId,
                        'firstname': address.firstname,
                        'lastname': address.lastname,
                        'middlename': address.middlename,
                        'prefix': address.prefix,
                        'suffix': address.suffix,
                        'vat_id': address.vatId,
                        'company': address.company,
                        'telephone': address.telephone,
                        'fax': address.fax,
                        'custom_attributes': address.customAttributes,
                        'save_in_address_book': address.saveInAddressBook
                    }
                }
            );

            if (cache && cacheable) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
            } else {
                storage.post(
                    serviceUrl, payload, false
                ).done(function (result) {
                    rateRegistry.set(address.getCacheKey(), result);
                    shippingService.setShippingRates(result);
                    var methodCode = '', whCode = '', whCodes = [], whCount = -1, whRates = [];

                    for(var counter in result) {
                        methodCode = result[counter].method_code;

                        // if (methodCode) {
                        //     whCode = methodCode.substring(methodCode.indexOf('_')+1, methodCode.length);

                        //     if (typeof whCodes[whCode] == 'undefined') {
                        //         whCount++;
                        //         owner.cartWarehouses.push(whCode);

                        //         whCodes[whCode] = whCount;
                        //         whRates[whCount] = {
                        //             code: whCode,
                        //             name: result[counter].carrier_title,
                        //             s_rates: []
                        //         };
                        //     }

                        //     whRates[whCodes[whCode]].s_rates.push(result[counter]);
                        // }

                        var carrierTitle = result[counter].carrier_title;
                        if (carrierTitle) {
                            // whCode = carrierTitle.substring(carrierTitle.indexOf('_')+1, carrierTitle.length);
                            // var whName = carrierTitle.substring(0, carrierTitle.indexOf('_'));
                            var whInfo = carrierTitle.substring(0, carrierTitle.indexOf('_multi_'));
                            var originalTitle = carrierTitle.substring(carrierTitle.indexOf('_multi_') + 7, carrierTitle.length);
                            whCode = whInfo.substring(whInfo.indexOf('_')+1, whInfo.length);
                            var whName = whInfo.substring(0, whInfo.indexOf('_'));
                            if (typeof whCodes[whCode] == 'undefined') {
                                whCount++;
                                owner.cartWarehouses.push(whCode);

                                whCodes[whCode] = whCount;
                                whRates[whCount] = {
                                    code: whCode,
                                    name: whName,
                                    s_rates: []
                                };
                            }
                            result[counter]['carrier_title'] = originalTitle;
                            whRates[whCodes[whCode]].s_rates.push(result[counter]);
                        }
                    }
                    multiwarehouse.setWarehouses(whRates);
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                    shippingService.isLoading(false);
                });
            }
        }
    };
});
