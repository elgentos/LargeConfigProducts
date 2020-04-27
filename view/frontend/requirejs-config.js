var config = {
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Elgentos_LargeConfigProducts/js/swatch-renderer-mixin': true
            },
            //'Magento_Swatches/js/configurable-customer-data': {
            //    'Elgentos_LargeConfigProducts/js/configurable-customer-data-mixin': true
            //},
            'Magento_ConfigurableProduct/js/configurable': {
                'Elgentos_LargeConfigProducts/js/configurable-mixin': true
            }
        }
    }
};
