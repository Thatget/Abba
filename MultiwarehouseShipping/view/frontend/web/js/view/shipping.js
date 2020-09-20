/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/shipping',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'mage/translate',
    'MW_MultiwarehouseShipping/js/model/multiwarehouse-shipping',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressConverter,
    quote,
    selectShippingAddress,
    setShippingInformationAction,
    stepNavigator,
    $t,
    multiwarehouse,
    shippingSaveProcessor
) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'MW_MultiwarehouseShipping/shipping',
            shippingMethodListTemplate: 'MW_MultiwarehouseShipping/shipping-address/shipping-method-list'
        },

        warehouses: multiwarehouse.getWarehouses(),

        /**
         * Set shipping information handler
         */
        setShippingInformation: function () {
            var methodWh = [],
                method_code = '',
                warehouseCode = '',
                shippingAddress,
                methodData = { warehouse: [] };

            $('input[type=radio].shipping-radio').each(function (i) {
                if ($(this).is(':checked')) {
                    warehouseCode = $(this).attr('name');
                    method_code = $(this).attr('method-wh');

                    methodData.warehouse.push(
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

            if (this.validateShippingInformation()) {
                shippingSaveProcessor.multiwarehouseShipping = methodData;

                setShippingInformationAction().done(
                    function () {
                        stepNavigator.next();
                    }
                );
            }
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            var shippingAddress,
                addressData,
                loginFormSelector = 'form[data-role=email-with-possible-login]',
                emailValidationResult = customer.isLoggedIn(),
                field;

            if (!quote.shippingMethod()) {
                this.errorValidationMessage($t('Please specify a shipping method.'));

                return false;
            }

            if (!this.validateWarehouses()) {
                this.errorValidationMessage('Please specify a shipping method for each of the warehouses.');
                return false;
            }

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }

            if (this.isFormInline) {
                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (emailValidationResult &&
                    this.source.get('params.invalid') ||
                    !quote.shippingMethod()['method_code'] ||
                    !quote.shippingMethod()['carrier_code']
                ) {
                    this.focusInvalid();

                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = addressConverter.formAddressDataToQuoteAddress(
                    this.source.get('shippingAddress')
                );

                //Copy form data to quote shipping address object
                for (field in addressData) {
                    if (addressData.hasOwnProperty(field) &&  //eslint-disable-line max-depth
                        shippingAddress.hasOwnProperty(field) &&
                        typeof addressData[field] != 'function' &&
                        _.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress[field] = addressData[field];
                    } else if (typeof addressData[field] != 'function' &&
                        !_.isEqual(shippingAddress[field], addressData[field])) {
                        shippingAddress = addressData;
                        break;
                    }
                }

                if (customer.isLoggedIn()) {
                    shippingAddress['save_in_address_book'] = 1;
                }
                selectShippingAddress(shippingAddress);
            }

            if (!emailValidationResult) {
                $(loginFormSelector + ' input[name=username]').focus();

                return false;
            }
            
            return true;
        },

        validateWarehouses: function () {
            var warehouses = this.warehouses();
            var count = 0;

            for (var counter in warehouses) {
                $('input[type=radio].shipping-radio').each(function (i) {
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
