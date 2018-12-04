<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 4-1-18
 * Time: 10:47
 */

namespace Elgentos\LargeConfigProducts\Block\Product\Renderer;

class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    const CUSTOM_SWATCH_RENDERER_TEMPLATE = 'Elgentos_LargeConfigProducts::product/view/renderer.phtml';
    const CUSTOM_CONFIGURABLE_RENDERER_TEMPLATE = 'Elgentos_LargeConfigProducts::product/view/type/options/configurable.phtml';

    /**
     * Return renderer template
     *
     * Template for product with swatches is different from product without swatches
     *
     * @return string
     */
    protected function getRendererTemplate()
    {
        return $this->isProductHasSwatchAttribute() ?
            self::CUSTOM_SWATCH_RENDERER_TEMPLATE : self::CUSTOM_CONFIGURABLE_RENDERER_TEMPLATE;
    }

}