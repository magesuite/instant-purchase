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
        quoteId = ko.observable(null),
        quoteItems = ko.observable(null),
        quoteTotals = ko.observable(null),
        purchaseCustomerData = customerData.get('instant-purchase'),
        instantPurchaseAvailable = ko.observable(null),
        customerPaymentToken = ko.observable(null),
        defaultBillingAddressId = ko.observable(null),
        defaultShippingAddressId = ko.observable(null),
        availableShippingMethods = ko.observableArray([]),
        selectedShippingMethod = ko.observable(null),
        customerAddresses = ko.observableArray([]),
        selectedBillingAddress = ko.observable(null),
        selectedShippingAddress = ko.observable(null);

    // Sets initial data based on customerData and subscribe to changes
    const setInstantPurchaseData = ({available, paymentToken, shippingAddress, billingAddress}) => {
        if (available) {
            instantPurchaseAvailable(available);
        }

        if (paymentToken) {
            customerPaymentToken(paymentToken)
        }

        if (shippingAddress?.id) {
            defaultShippingAddressId(shippingAddress.id)
        }

        if (billingAddress?.id) {
            defaultBillingAddressId(billingAddress.id)
        }
    };
    setInstantPurchaseData(purchaseCustomerData());
    purchaseCustomerData.subscribe(setInstantPurchaseData);

    // Set data based on instant purchase quote when it gets created or reloaded
    quoteData.subscribe(({items, totals, quote, shippingMethods, addresses}) => {
        if (!items || !totals || !quote || !shippingMethods || !addresses) {
            return;
        }

        if (items) { quoteItems(items) };
        if (totals) { quoteTotals(totals) };
        if (shippingMethods) { availableShippingMethods(shippingMethods) };
        if (addresses) { customerAddresses(addresses) };

        const {
            id,
            shipping_carrier_code,
            shipping_method_code,
            shipping_address,
            billing_address,
        } = quote;

        if (id) { quoteId(id) };

        if (shipping_carrier_code && shipping_method_code) {
            const shippingMethod = availableShippingMethods().find(method => {
                return method.carrier_code == shipping_carrier_code && method.method_code == shipping_method_code
            });
            selectedShippingMethod(shippingMethod)
        };

        if (shipping_address?.customer_address_id) {
            const shippingAddress = customerAddresses()?.find(item => (
                item.entity_id == shipping_address.customer_address_id
            )) || null;

            selectedShippingAddress(shippingAddress);
        };

        if (billing_address?.customer_address_id) {
            const billingAddress = customerAddresses()?.find(item => (
                item.entity_id == billing_address.customer_address_id
            )) || null;

            selectedBillingAddress(billingAddress);
        };
    });

    return {
        quoteId,
        quoteItems,
        quoteTotals,
        instantPurchaseAvailable,
        customerPaymentToken,
        availableShippingMethods,
        selectedShippingMethod,
        selectedShippingAddress,
        selectedBillingAddress,
        customerAddresses,

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

            const
                shippingAddressId = useDefault ? defaultShippingAddressId() : selectedShippingAddress()?.entity_id,
                billingAddressId = useDefault ? defaultBillingAddressId() : selectedBillingAddress()?.entity_id,
                shippingMethod = useDefault ? null : selectedShippingMethod();

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

            if (shippingMethod) {
                const { carrier_code, method_code } = shippingMethod;

                if (carrier_code && method_code) {
                    formData.push({
                        name: 'instant_purchase_carrier',
                        value: carrier_code
                    });
                    formData.push({
                        name: 'instant_purchase_shipping',
                        value: method_code
                    });
                }
            }

            return formData;
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
            return priceUtils.formatPrice(price, quoteData().basePriceFormat);
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
