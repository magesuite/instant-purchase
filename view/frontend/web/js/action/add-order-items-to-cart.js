define([
    'jquery',
    'mage/url',
    'MageSuite_InstantPurchase/js/model/instant-purchase'
], function (
    $,
    urlBuilder,
    instantPurchaseModel
) {
    const addToCartUrl = urlBuilder.build("instant_purchase/cart/add");

    /**
     * Checks if form contains any products to be added to cart
     *
     * @param {Array}
     * @return {Boolean}
     */
    const hasAnyProductSelected = (formData) => formData.some(key => {
        return key.name.indexOf('reorder_item') !== -1;
    });

    /**
     * Collects instant purchase required data and loads quote
     *
     * @return {(jqXHR|Deferred)}
     */
    return function () {
        const formData = instantPurchaseModel.getSerializedFormData();

        if (!formData || !hasAnyProductSelected(formData)) {
            return $.Deferred().reject();
        }

        return $.ajax({
            type: "POST",
            url: addToCartUrl,
            data: formData,
        });
    };
});
