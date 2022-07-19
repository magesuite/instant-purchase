var config = {
    map: {
        '*': {
            instantPurchaseForm: 'MageSuite_InstantPurchase/js/instant-purchase-form',
        }
    },
    config: {
        mixins: {
            'MageSuite_Pwa/js/pwa-a2hs-guide': {
                'MageSuite_InstantPurchase/js/pwa-a2hs-guide-ext': true,
            },
        },
    },
};
