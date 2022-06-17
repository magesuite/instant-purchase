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
                totalsLabel: $.mage.__('Order Total'),
                showOrderDetails: $.mage.__('Show details'),
                hideOrderDetails: $.mage.__('Hide details'),
                defaultPaymentMethodName: $.mage.__('Last used Credit Card'),
            },
            placeOrderButtonIcon: 'images/icons/arrow_next.svg',
        },
        instantPurchaseAvailable: instantPurchaseModel.instantPurchaseAvailable,
        quoteItems: instantPurchaseModel.quoteItems,
        quoteTotals: instantPurchaseModel.quoteTotals,
        formatPrice: instantPurchaseModel.formatPrice,
        showOrderDetails: ko.observable(false),
        customerAddresses: instantPurchaseModel.customerAddresses,
        selectedShippingAddress: instantPurchaseModel.selectedShippingAddress,
        selectedBillingAddress: instantPurchaseModel.selectedBillingAddress,
        availableShippingMethods: instantPurchaseModel.availableShippingMethods,
        selectedShippingMethod: instantPurchaseModel.selectedShippingMethod,

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

            this.toggleOrderDetails = this.toggleOrderDetails.bind(this);
            this.handleSelectChange = this.handleSelectChange.bind(this);
            this.formatShippingMethodLabel = this.formatShippingMethodLabel.bind(this);
            this.formatAddressLabel = this.formatAddressLabel.bind(this);
        },

        /**
         * Toggles order details.
         */
        toggleOrderDetails: function() {
            this.showOrderDetails(!this.showOrderDetails())
        },

        /**
         * Returns shipping method string in a format to be used as a label.
         *
         * @param {Object} shippingMehod
         * @return {String}
         */
        formatShippingMethodLabel({carrier_title, method_title, price_incl_tax}) {
            return `${carrier_title} - ${method_title} - ${this.formatPrice(price_incl_tax)}`
        },

        /**
         * Returns address in a format to be used as a label.
         *
         * @param {Object} address
         * @return {String}
         */
        formatAddressLabel({firstname, lastname, street, city, postcode, country_id }) {
            return `${firstname}, ${lastname}, ${street}, ${city}, ${postcode}, ${country_id}`;
        },

        /**
         * Method to be used in as select change event handler,
         * which reloads the quote when value changes changes.
         * It uses the subscription workaround,
         * because the change event itself gets the old value instead of new one.
         * We subscribe to the related value model, and after we handle it, we cancel a subscription.
         * Original event check assures user interaction.
         *
         * @param {Event} event
         * @param {Event} valueModel - same model we pass to select as value binding
         */
        handleSelectChange: function(event, valueModel) {
            if (event.originalEvent) {
                const subscription = valueModel.subscribe(() => {
                    this.reloadQuote();
                    subscription.dispose();
                })
            }
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
