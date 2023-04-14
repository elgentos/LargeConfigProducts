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
                var baseUrl=this.options.baseUrl;
				if (baseUrl === undefined) { 
					baseUrl=window.location.origin + '/';
					//console.log('baseUrl ' + baseUrl);
				}                
                $.ajax({
                    url: baseUrl + 'lcp/fetch/productOptions',
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
                    var selectBoxes = $('select.swatch-select'), updatedSelectBoxes = [];
                    selectBoxes.each(function (index, selectBox) {
                        var $selectBox = $(selectBox);

                        var options = $selectBox.find('option[value!="0"]');
                        if (options.length <= 1) {
                            $selectBox.val(options.first().val());
                            updatedSelectBoxes.push(selectBox);
                        }
                    });

                    $(updatedSelectBoxes).change();

                    // Preselect swatch if only 1 swatch exists
                    const selectSwatch = document.querySelectorAll('.swatch-option');
                    if($(selectSwatch).length == 1) {
                        $(selectSwatch).trigger("click");
                    }
                });
            },

            updateBaseImage: function (images, context, isInProductView) {

                // If no images are set, do not replace existing image
                if (images.length > 0 && images[0].full == null) {
                    return;
                }

                var justAnImage = images[0],
                    initialImages = this.options.mediaGalleryInitial,
                    imagesToUpdate,
                    gallery = context.find(this.options.mediaGallerySelector).data('gallery'),
                    isInitial;

                if (isInProductView) {
                    if (_.isUndefined(gallery)) {
                        context.find(this.options.mediaGallerySelector).on('gallery:loaded', function () {
                            this.updateBaseImage(images, context, isInProductView);
                        }.bind(this));

                        return;
                    }

                    imagesToUpdate = images.length ? this._setImageType($.extend(true, [], images)) : [];
                    isInitial = _.isEqual(imagesToUpdate, initialImages);

                    if (this.options.gallerySwitchStrategy === 'prepend' && !isInitial) {
                        imagesToUpdate = imagesToUpdate.concat(initialImages);
                    }

                    imagesToUpdate = this._setImageIndex(imagesToUpdate);

                    gallery.updateData(imagesToUpdate);
                    this._addFotoramaVideoEvents(isInitial);
                } else if (justAnImage && justAnImage.img) {
                    context.find('.product-image-photo').attr('src', justAnImage.img);
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
