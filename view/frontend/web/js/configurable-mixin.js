define([
    'jquery',
    'mage/url'
], function ($, url) {
    'use strict';

    return function (widget) {
        $.widget('mage.configurable', widget, {
            // Load jsonConfig through AJAX call instead of in-line
            _create: function () {
                var that = this;
                var productData = this._determineProductData();
                $.ajax({
                    url: url.build('lcp/fetch/productOptions'),
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        productId: productData.productId
                    },
                    cache: true
                }).done(function (data) {
                    $('#product-options-spinner').remove();
                    $('#product-options-wrapper > .fieldset').show();
                    that.options.spConfig = data;
                    that._trueCreate();
                });
            },

            _trueCreate: function () {
                // Initial setting of various option values
                this._initializeOptions();

                // Override defaults with URL query parameters and/or inputs values
                this._overrideDefaults();

                // Change events to check select reloads
                this._setupChangeEvents();

                // Fill state
                this._fillState();

                // Setup child and prev/next settings
                this._setChildSettings();

                // Setup/configure values to inputs
                this._configureForValues();

                $(this.element).trigger('configurable.initialized');
            },

            // Copied from swatch-renderer.js
            _determineProductData: function () {
                // Check if product is in a list of products.
                var productId,
                    isInProductView = false;

                productId = this.element.parents('.product-item-details')
                    .find('.price-box.price-final_price').attr('data-product-id');

                if (!productId) {
                    // Check individual product.
                    productId = $('[name=product]').val();
                    isInProductView = productId > 0;
                }

                return {
                    productId: productId,
                    isInProductView: isInProductView
                };
            },
        });

        return $.mage.configurable;
    }
});
