define([
    'jquery',
    'uiComponent',
    'MageSuite_InstantPurchase/js/model/instant-purchase',
    'MageSuite_InstantPurchase/js/action/instant-purchase-create'
], function (
    $,
    Component,
    instantPurchaseModel,
    createInstantPurchase
) {
    return Component.extend({
        defaults: {
            template: 'MageSuite_InstantPurchase/instant-purchase/instant-purchase-button',
            text: {
                createReorderButtonLabel: $.mage.__('Rebuy now'),
            },
            createReorderButtonIcon: 'images/icons/arrow_next.svg',
        },
        showButton: instantPurchaseModel.instantPurchaseAvailable,
        /**
         * Calls instant purchase create action
         * and shows offcanvas with instant purchase summary
         */
        createInstantPurchase: function () {
            createInstantPurchase(true).then(() => {
                $('body').trigger('instantPurchaseOffcanvasShow');
            });
        }
    });
});

