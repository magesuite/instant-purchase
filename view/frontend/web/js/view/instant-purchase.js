define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'MageSuite_InstantPurchase/js/model/instant-purchase',
    'MageSuite_InstantPurchase/js/action/instant-purchase-create',
    'MageSuite_InstantPurchase/js/action/instant-purchase-place-order'
], function (
    ko,
    $,
    Component,
    customerData,
    $t,
    instantPurchaseModel,
    createInstantPurchase,
    placeOrderAction
) {
    return Component.extend({
        defaults: {
            template: 'MageSuite_InstantPurchase/instant-purchase/instant-purchase',
            formElementSelector: '#instant_purchase',
            text: {
                offcanvasTitle: $.mage.__('Instant purchase'),
                placeOrderButtonLabel: $.mage.__('Buy now'),
                totalsLabel: $.mage.__('Order total'),
                qtyLabel: $.mage.__('Qty'),
                showOrderDetails: $.mage.__('Show details'),
                hideOrderDetails: $.mage.__('Hide details'),
            },
            placeOrderButtonIcon: 'images/icons/arrow_next.svg',
        },
        instantPurchaseAvailable: instantPurchaseModel.instantPurchaseAvailable,
        availableShippingMethods: instantPurchaseModel.availableShippingMethods,
        selectedShippingMethodValue: instantPurchaseModel.selectedShippingMethodValue,
        selectedShippingAddressId: instantPurchaseModel.selectedShippingAddressId,
        selectedBillingAddressId: instantPurchaseModel.selectedBillingAddressId,
        quoteData: instantPurchaseModel.quoteData,
        formatPrice: instantPurchaseModel.formatPrice,
        showOrderDetails: ko.observable(false),

        initialize: function() {
            this._super();

            /**
             * Sets form element, where order data gets collected from,
             * based on selector passed from config
             */
            const $formElement = $(`${this.formElementSelector}`);
            if ($formElement.length) {
                instantPurchaseModel.setFormElement($formElement);
            }

            // customerData.reload(['instant-purchase'], true);

            this.toggleOrderDetails = this.toggleOrderDetails.bind(this);
            this.handleShippingMethodChange = this.handleShippingMethodChange.bind(this);
            this.handleBillingAddressChange = this.handleBillingAddressChange.bind(this);
            this.handleShippingAddressChange = this.handleShippingAddressChange.bind(this);
        },

        /**
         * Pulls array with available customer addresses from quote.
         *
         * @return {Array}
         */
        getAllAddresses: function() {
            return this.quoteData()?.addresses || null;
        },

        /**
         * Returns current shipping method in a format required by BE enpoint
         * to be used in shipping method select as a value.
         *
         * @return {Array}
         */
        getCurrentShippingMethodFormattedValue() {
            const carrierCode = this.quoteData()?.quote?.shipping_carrier_code;
            const methodCode = this.quoteData()?.quote?.shipping_method_code;

            if (!carrierCode || !methodCode) {
                return null;
            }

            return `${carrierCode}|${methodCode}`
        },

        /**
         * Toggles order details.
         */
        toggleOrderDetails: function() {
            this.showOrderDetails(!this.showOrderDetails())
        },

        /**
         * Returns shipping method in a format to be used in shipping method select as a value.
         *
         * @param {Object} shippingMehod
         * @return {String}
         */
        formatShippingMethodValue(shippingMethod) {
            if (!shippingMethod) {
                return null;
            }

            return `${shippingMethod.carrier_code}|${shippingMethod.method_code}`;
        },

        /**
         * Sets shipping method value on elect element change and reloads quote.
         * Works only if event was triggered by the user.
         *
         * @param {Event} event
         */
        handleShippingMethodChange: function(obj, event) {
            if (event.originalEvent) {
                instantPurchaseModel.setShippingMethodValue(event.currentTarget.value);
                this.reloadQuote();
            }
        },

        /**
         * Sets billing address id on elect element change and reloads quote.
         * Works only if event was triggered by the user.
         *
         * @param {Event} event
         */
        handleBillingAddressChange: function(obj, event) {
            if (event.originalEvent) {
                instantPurchaseModel.setBillingAddressId(event.currentTarget.value);
                this.reloadQuote();
            }
        },

        /**
         * Sets shipping address id on elect element change and reloads quote.
         * Works only if event was triggered by the user.
         *
         * @param {Event} event
         */
        handleShippingAddressChange: function(obj, event) {
            if (event.originalEvent) {
                instantPurchaseModel.setShippingAddressId(event.currentTarget.value);
                this.reloadQuote();
            }

        },

        /**
         * Pulls order totals object from quote.
         *
         * @return {Object} event
         */
        getTotals: function () {
            return this.quoteData()?.totals || null;
        },

        /**
         * Triggers create instant purchase action that reloads quote.
         */
        reloadQuote: function() {
            createInstantPurchase();
        },

        /**
         * Triggers place order action.
         */
        placeOrder: function () {
            placeOrderAction()
        }
    });
});
