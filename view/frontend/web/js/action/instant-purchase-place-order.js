define([
    'jquery',
    'mage/url',
    'MageSuite_InstantPurchase/js/model/instant-purchase',
], function (
    $,
    urlBuilder,
    instantPurchaseModel
) {
    const purchaseUrl = urlBuilder.build('instant_purchase/purchase/instant');

    /**
     * Checks if quote is created, places an order,
     * and redirects directly to success page afterward.
     *
     * @return {(jqXHR|Deferred)}
     */
    return function () {
        const quoteId = instantPurchaseModel.quoteId();

        if (!quoteId) {
            return $.Deferred().reject();
        }

        return $.ajax({
            url: purchaseUrl,
            data: {'quote_id': quoteId},
            type: 'post',
            dataType: 'json',
            beforeSend: function () {
                $(document.body).trigger('processStart');
            }
        }).always(function () {
            $(document.body).trigger('processStop');
        }).done(() => {
            if (!data.error) {
                window.location.replace(urlBuilder.build('checkout/onepage/success'));
            }
        })
    };
});
