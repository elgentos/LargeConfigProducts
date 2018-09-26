define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            // Load jsonConfig through AJAX call instead of in-line
            _init: function () {
                if (!_.isNull(this.options.jsonConfig)) {
                    return;
                }
                var that = this;
                var productData = this._determineProductData();
                $.ajax({
                    url: '/lcp/fetch/productOptions',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        productId: productData.productId
                    },
                    cache: true
                }).done(function (data) {
                    $('#product-options-spinner').remove();
                    $('#product-options-wrapper > .fieldset').show();
                    that.options.jsonConfig = data;
                    that._trueInit();

                    // Preselect option if only 1 option exists
                    const selectBoxes = document.querySelectorAll('select.swatch-select');
                    selectBoxes.forEach(function (selectBox) {
                        if (selectBox.options.length <= 2) {
                            selectBox.selectedIndex = selectBox.options.length - 1;
                            $(selectBox).change();
                        }
                    });
                });
            },

            updateBaseImage: function (images, context, isInProductView, eventName) {
                // If no images are set, do not replace existing image
                if (images.length > 0 && images[0].full == null) {
                    return;
                }

                var gallery = context.find(this.options.mediaGallerySelector).data('gallery');

                if (eventName === undefined) {
                    this.processUpdateBaseImage(images, context, isInProductView, gallery);
                } else {
                    context.find(this.options.mediaGallerySelector).on('gallery:loaded', function (loadedGallery) {
                        loadedGallery = context.find(this.options.mediaGallerySelector).data('gallery');
                        this.processUpdateBaseImage(images, context, isInProductView, loadedGallery);
                    }.bind(this));
                }
            },

            _trueInit: function () {
                if (_.isEmpty(this.options.jsonConfig.images)) {
                    this.options.useAjax = true;
                    // creates debounced variant of _LoadProductMedia()
                    // to use it in events handlers instead of _LoadProductMedia()
                    this._debouncedLoadProductMedia = _.debounce(this._LoadProductMedia.bind(this), 500);
                }

                if (this.options.jsonConfig !== '' && this.options.jsonSwatchConfig !== '') {
                    // store unsorted attributes
                    this.options.jsonConfig.mappedAttributes = _.clone(this.options.jsonConfig.attributes);
                    this._sortAttributes();
                    this._RenderControls();
                    this._setPreSelectedGallery();
                    $(this.element).trigger('swatch.initialized');
                } else {
                    console.log('SwatchRenderer: No input data received');
                }
                this.options.tierPriceTemplate = $(this.options.tierPriceTemplateSelector).html();
            }
        });

        return $.mage.SwatchRenderer;
    }
});
