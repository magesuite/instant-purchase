define([
    'jquery',
    'uiComponent',
    'MageSuite_InstantPurchase/js/model/instant-purchase',
], function (
    $,
    Component,
    instantPurchaseModel,
) {
    return Component.extend({
        defaults: {
            template: 'MageSuite_InstantPurchase/instant-purchase/instant-purchase-products',
            text: {
                qtyLabel: $.mage.__('Qty'),
            },
        },
        quoteItems: instantPurchaseModel.quoteItems,
        formatPrice: instantPurchaseModel.formatPrice
    });
});

