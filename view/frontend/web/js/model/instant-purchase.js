define([
    'ko',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
], function (
    ko,
    customerData,
    priceUtils
) {
    const
        formElement = ko.observable(null),
        quoteData = ko.observable(null),
        instantPurchaseAvailable = ko.observable(null),
        customerPaymentToken = ko.observable(null),
        selectedShippingMethodValue = ko.observable(null),
        selectedBillingAddressId = ko.observable(null),
        selectedShippingAddressId = ko.observable(null),
        defaultBillingAddressId = ko.observable(null),
        defaultShippingAddressId = ko.observable(null),
        availableShippingMethods = ko.observableArray([]),
        purchaseCustomerData = customerData.get('instant-purchase');

    // Sets initial data based on customerData
    purchaseCustomerData.subscribe(({available, paymentToken, shippingAddress, billingAddress}) => {
        if (available) {
            instantPurchaseAvailable(available);
        }

        if (paymentToken) {
            customerPaymentToken(paymentToken)
        }

        if (shippingAddress?.id) {
            defaultShippingAddressId(shippingAddress.id)
            selectedShippingAddressId(shippingAddress.id);
        }

        if (billingAddress?.id) {
            defaultBillingAddressId(billingAddress.id)
            selectedBillingAddressId(billingAddress.id)
        }
    });

    // Set data based on instant purchase quote when it gets created or reloaded
    quoteData.subscribe(({quote, shippingMethods}) => {
        if (shippingMethods) {
            availableShippingMethods(shippingMethods)
        };

        const {
            shipping_carrier_code,
            shipping_method_code,
            shipping_address,
            billing_address
        } = quote;

        if (shipping_address?.id) {
            selectedShippingAddressId(shipping_address.id);
        }

        if (billing_address?.id) {
            selectedBillingAddressId(billing_address.id);
        }

        if (shipping_carrier_code && shipping_method_code) {
            selectedShippingMethodValue(`${shipping_carrier_code}|${shipping_method_code}`);
        }
    });

    return {
        quoteData,
        instantPurchaseAvailable,
        customerPaymentToken,
        availableShippingMethods,
        selectedShippingAddressId,
        selectedBillingAddressId,
        selectedShippingMethodValue,

        /**
         * Returns serialized form data
         *
         * @param {HTMLElement} $loader - Loader DOM element.
         * @return {Array}
         */
        getSerializedFormData: function () {
            return formElement()?.serializeArray() || null;
        },

        /**
         * Collects form data enhanced with instant purchase data that are needed
         * to create instant purchase quote - payment token, shipping/billing address, shipping method
         *
         * @param {Boolean} useDefault - decides if to use default shipping & addresses data.
         * @return {Array}
         */
        collectOrderData: function (useDefault) {
            const formData = this.getSerializedFormData();

            if (!formData) {
                return null;
            }

            if (customerPaymentToken()) {
                formData.push({
                    name: 'instant_purchase_payment_token',
                    value: customerPaymentToken().publicHash
                });
            }

            if (useDefault) {
                this.restoreDefaultData();
            }

            const
                shippingAddressId = selectedShippingAddressId(),
                billingAddressId = selectedBillingAddressId(),
                shippingMethodValue = selectedShippingMethodValue();

            if (shippingAddressId) {
                formData.push({
                    name: 'instant_purchase_shipping_address',
                    value: shippingAddressId
                });
            }

            if (billingAddressId) {
                formData.push({
                    name: 'instant_purchase_billing_address',
                    value: billingAddressId
                });
            }

            if (shippingMethodValue) {
                const [carrierCode, shippingMethod] = shippingMethodValue.split('|');

                if (carrierCode && shippingMethod) {
                    formData.push({
                        name: 'instant_purchase_carrier',
                        value: carrierCode
                    });
                    formData.push({
                        name: 'instant_purchase_shipping',
                        value: shippingMethod
                    });
                }
            }

            return formData;
        },

        /**
         * Restores default shipping & addresses values for instant purchase
         */
        restoreDefaultData: function() {
            const
                billingAddressId = defaultBillingAddressId(),
                shippingAddressId = defaultShippingAddressId();

            if (billingAddressId && shippingAddressId) {
                selectedBillingAddressId(billingAddressId);
                selectedShippingAddressId(shippingAddressId);
            }

            selectedShippingMethodValue(null);
        },

        /**
         * Sets the form element,
         * that will be used for collecting order data
         */
        setFormElement: function(element) {
            if (element) {
                formElement(element);
            }
        },

        /**
         * Returns formatted price
         *
         * @param {Number} price
         * @return {String}
         */
        formatPrice: function(price) {
            return priceUtils.formatPrice(price, this.quoteData().basePriceFormat);
        },

        /**
         * Sets shipping method value observable
         *
         * @param {String} shippingMethodValue
         */
        setShippingMethodValue: function (shippingMethodValue) {
            if (shippingMethodValue) {
                selectedShippingMethodValue(shippingMethodValue)
            }
        },

        /**
         * Sets shipping address Id observable
         *
         * @param {String} shippingAddressId
         */
        setShippingAddressId: function (shippingAddressId) {
            if (shippingAddressId) {
                selectedShippingAddressId(shippingAddressId)
            }
        },

        /**
         * Sets billing address Id observable
         *
         * @param {String} billingAddressId
         */
        setBillingAddressId: function (billingAddressId) {
            if (billingAddressId) {
                selectedBillingAddressId(billingAddressId)
            }
        },

        /**
         * Sets quote data observable
         *
         * @param {String} billingAddressId
         */
        setQuoteData: function (quote) {
            if (quote) {
                quoteData(quote);
            }
        },
    };
});
