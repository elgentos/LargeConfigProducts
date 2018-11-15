<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 21-2-18
 * Time: 13:53
 */

namespace Elgentos\LargeConfigProducts\Plugin\Model;

use Elgentos\LargeConfigProducts\Cache\CredisClientFactory;
use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Store\Model\StoreManagerInterface;
class AttributeOptionProviderPlugin
{
    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var \Credis_Client|null */
    protected $credis;
    /**
     * AttributeOptionProviderPlugin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param CredisClientFactory $credisClientFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CredisClientFactory $credisClientFactory
    ) {
        $this->credis       = $credisClientFactory->create();
        $this->storeManager = $storeManager;
    }
    public function beforeGetAttributeOptions(
        \Magento\ConfigurableProduct\Model\AttributeOptionProvider $subject,
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $superAttribute,
        $productId
    ) {
        /**
         * The currentStoreId that is being set in the emulation in PrewarmerCommand is somehow lost in the call
         * stack. This plugin uses the store ID found in the Redis DB to re-set the current store so the translated
         * attribute option labels are retrieved correctly.
         */
        if (PHP_SAPI == 'cli') {
            $prewarmCurrentStore = $this->credis->get(Prewarmer::PREWARM_CURRENT_STORE);
            if ($prewarmCurrentStore) {
                $this->storeManager->setCurrentStore($prewarmCurrentStore);
            }
        }
    }
}