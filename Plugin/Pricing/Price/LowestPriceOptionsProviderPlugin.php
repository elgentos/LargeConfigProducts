<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 17-1-18
 * Time: 21:06
 */
namespace Elgentos\LargeConfigProducts\Plugin\Pricing\Price;

use Magento\Store\Model\StoreManagerInterface;

class LowestPriceOptionsProviderPlugin
{
    protected $storeManager;

    /**
     * LowestPriceOptionsProviderPlugin constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function beforeGetProducts(\Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider $subject, \Magento\Catalog\Api\Data\ProductInterface $product)
    {
        /**
         * The currentStoreId that is being set in the emulation in PrewarmerCommand is somehow lost in the call
         * stack. This plugin uses the store ID found in the product to re-set the current store so the prices
         * are retrieved correctly.
         */
        $this->storeManager->setCurrentStore($product->getStoreId());
    }
}