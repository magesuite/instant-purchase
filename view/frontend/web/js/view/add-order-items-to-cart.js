define([
    'jquery',
    'uiComponent',
    'MageSuite_InstantPurchase/js/action/add-order-items-to-cart'
], function (
    $,
    Component,
    addOrderItemsToCart
) {
    return Component.extend({
        defaults: {
            template: 'MageSuite_InstantPurchase/instant-purchase/add-order-items-to-cart',
            text: {
                addToCart: $.mage.__('Add selected to cart')
            }
        },

        /**
         * Calls add to cart action and opens minicart
         */
        addItemsToCart: function () {
            addOrderItemsToCart().then(() => {
                $("[data-block='minicart']").trigger("openMinicart");
            });
        }
    });
});

