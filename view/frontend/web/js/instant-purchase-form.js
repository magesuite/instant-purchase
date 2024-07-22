define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    $.widget('magesuite.instantPurchaseForm', {
        options: {
            baseClass: 'cs-instant-purchase-form',
            toggleTextShow: $.mage.__('Show'),
            toggleTextHide: $.mage.__('Hide'),
        },
        _create: function() {
            this.options = {
                ...{
                    formClass: `${this.options.baseClass}__form`,
                    formLockedClass: `${this.options.baseClass}__form--locked`,
                    orderBodyClass: `${this.options.baseClass}__order`,
                    orderVisibleClass: `${this.options.baseClass}__order--visible`,
                    orderHasSelectedClass: `${this.options.baseClass}__order--has-selected`,
                    orderInfoRowClass: `${this.options.baseClass}__order-info`,
                    orderCheckboxClass: `${this.options.baseClass}__order-checkbox`,
                    productCheckboxClass: `${this.options.baseClass}__product-checkbox`,
                    orderToggleClass: `${this.options.baseClass}__order-toggle`,
                    orderToggleTextClass: `${this.options.baseClass}__order-toggle-text`,
                    actionsWrapperClass: `${this.options.baseClass}__actions`,
                    actionsDisabledClass: `${this.options.baseClass}__actions--disabled`,
                },
                ...this.options
            }

            this.$orders = $(`.${this.options.orderBodyClass}`);
            this.attachEventListeners();
            this.setActionsAvailability();
            $(`.${this.options.formClass}`).removeClass(this.options.formLockedClass);
        },

        /**
         * Attaches event listeners for all form elements
         */
        attachEventListeners: function() {
            $(`.${this.options.orderInfoRowClass}`, this.$orders).click(this.orderInfoRowClickHandler.bind(this));
            $(`.${this.options.orderCheckboxClass}`, this.$orders).click(this.orderCheckboxHandler.bind(this));
            $(`.${this.options.productCheckboxClass}`, this.$orders).click(this.productCheckboxHandler.bind(this));
            $(`.${this.options.orderToggleClass}`, this.$orders).click(this.orderToggleHandler.bind(this));
        },

        /**
         * Handles click on order row (excluding other clickable elements).
         */
        orderInfoRowClickHandler: function(event) {
            if ($(event.target).is(`input, label, a`)) {
                return;
            }

            $(event.currentTarget).find(`.${this.options.orderToggleClass}`).first().trigger('click');
        },

        /**
         * Toggles order details visibility
         */
        orderToggleHandler: function(event) {
            event.stopPropagation();
            const
                $target = $(event.currentTarget),
                $text = $target.find(`.${this.options.orderToggleTextClass}`),
                $order = $target.closest(`.${this.options.orderBodyClass}`);

            if ($order.hasClass(this.options.orderVisibleClass)) {
                $order.removeClass(this.options.orderVisibleClass)
                $text.text(this.options.toggleTextShow);
            } else {
                $text.text(this.options.toggleTextHide);
                $order.addClass(this.options.orderVisibleClass)
            }
        },

        /**
         * Handles product checkbox click,
         * to select/deselect whole order checkbox
         */
        productCheckboxHandler: function(event) {
            event.stopPropagation();
            const isChecked = event.currentTarget.checked;
            const $order = $(event.currentTarget).closest(`.${this.options.orderBodyClass}`);
            const orderCheckbox = $order.find(`.${this.options.orderCheckboxClass}`)[0];
            const anyProductSelected = $order.find(`.${this.options.productCheckboxClass}:checked`).length;

            if (!isChecked && orderCheckbox.checked) {
                orderCheckbox.checked = false;
            } else if (isChecked && !orderCheckbox.checked) {
                const allProductsSelected = !$order.find(`.${this.options.productCheckboxClass}:not(:checked)`).length;

                if (allProductsSelected) {
                    orderCheckbox.checked = true;
                }
            }

            if (anyProductSelected) {
                $order.addClass(this.options.orderHasSelectedClass);
            } else {
                $order.removeClass(this.options.orderHasSelectedClass);
            }

            this.setActionsAvailability();

            return;
        },

        /**
         * Handles order checkbox click,
         * to select/deselect all products
         */
        orderCheckboxHandler: function(event) {
            event.stopPropagation();
            const isChecked = event.currentTarget.checked;
            const $order = $(event.currentTarget).closest(`.${this.options.orderBodyClass}`);
            const $notSelectedProductCheckboxes = $order.find(`.${this.options.productCheckboxClass}:not(:checked)`);

            if (isChecked && $notSelectedProductCheckboxes.length) {
                $notSelectedProductCheckboxes.each((i, checkbox) => {checkbox.checked = true});
            } else if (!isChecked) {
                $order.find(`.${this.options.productCheckboxClass}`).each((i, checkbox) => {checkbox.checked = false});
            }

            if (isChecked && !$order.hasClass(this.options.orderVisibleClass)) {
                $order.addClass(this.options.orderVisibleClass);
            }

            this.setActionsAvailability();
        },

        /**
         * Removes class from actions wrapper,
         * that lock events on buttons when there are is no product selected
         */
        setActionsAvailability: function() {
            const $actionsWrapper = $(`.${this.options.actionsWrapperClass}`);
            const anyProductSelected = $(`.${this.options.productCheckboxClass}:checked`, this.$orders).length;

            $actionsWrapper.toggleClass(this.options.actionsDisabledClass, !anyProductSelected);
        }
    });

    return $.magesuite.instantPurchaseForm;
});
