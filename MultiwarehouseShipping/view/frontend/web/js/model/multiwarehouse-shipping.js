/**
 * Mapped this js file to add custom code for delta shipping
 */
/*global define*/
define(
    [
        'ko'
    ],
    function (ko) {
        "use strict";
        var warehouses = ko.observableArray([]);
        return {
            setWarehouses: function (warehouseData) {
                warehouses(warehouseData);
            },

            getWarehouses: function () {
                return warehouses;
            }
        };
    }
);
