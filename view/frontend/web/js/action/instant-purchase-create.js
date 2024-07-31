define([
    'jquery',
    'mage/url',
    'MageSuite_InstantPurchase/js/model/instant-purchase',
], function ($, urlBuilder, instantPurchaseModel) {

    /**
     * Checks if form contains any products to be reordered
     *
     * @param {Array}
     * @return {Boolean}
     */
    const hasAnyProductForReorder = (formData) => formData.some(key => {
        return (
            key.name.indexOf('reorder_item') !== -1 ||
            key.name === 'product' ||
            key.name.indexOf('cart') !== -1
        )
    });

    const endpoint = urlBuilder.build('instant_purchase/purchase/quotedetails');

    /**
     * Collects instant purchase required data and loads quote
     * @param {Boolean} useDefaultData - decides if to use default shipping & addresses data.
     * @return {(jqXHR|Deferred)}
     */
    return function(useDefaultData) {
        var formData = instantPurchaseModel.collectOrderData(useDefaultData);

        if (!formData || !hasAnyProductForReorder(formData)) {
            return $.Deferred().reject();
        }

        formData.push({
            name: 'use_default_data',
            value: useDefaultData ?? 0
        })

        return $.ajax({
            url: endpoint,
            data: formData,
            type: 'post',
            dataType: 'json',
            beforeSend: function () {
                $(document.body).trigger('processStart');
            }
        }).always(function () {
            $(document.body).trigger('processStop');
        }).done(quoteSummaryData => {
            instantPurchaseModel.setQuoteData(quoteSummaryData);
            return quoteSummaryData;
        })
    }
});
