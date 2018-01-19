define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.selectSwatch', widget, {
            selectDefaultSwatchOptions: function () {
                var swatchWidget = $(this.options.selectors.swatchSelector).data(this.options.swatchWidgetName);

                // Add check for swatchOptions to not give error when it is not yet set
                if (!swatchWidget || !swatchWidget._EmulateSelectedByAttributeId || !this.options.swatchOptions) {
                    return;
                }
                swatchWidget._EmulateSelectedByAttributeId(
                    this.options.swatchOptions.defaultValues, this.options.clickEventName
                );
            }
        });

        return $.mage.SwatchRenderer;
    }
});