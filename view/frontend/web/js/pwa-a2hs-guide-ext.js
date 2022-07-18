/**
 * Change wording for PWA Add to Homescreen guide
 */
define(['jquery'], function($) {
    'use strict';
    return function(PwaA2hsGuide) {
        return PwaA2hsGuide.extend({
            defaults: {
                headerIOS: $.mage.__('Re-order products in 4 clicks.'),
                headerAndroid: $.mage.__('Re-order products in 4 clicks.'),
                headerDesktop: $.mage.__('Re-order products in 4 clicks.'),
            },
        });
    };
});
