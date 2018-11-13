<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 21-2-18
 * Time: 13:53
 */
namespace Elgentos\LargeConfigProducts\Plugin\Model;
use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Store\Model\StoreManagerInterface;
use Credis_Client;
class AttributeOptionProviderPlugin
{
    protected $storeManager;
    protected $credis;
    /**
     * AttributeOptionProviderPlugin constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DeploymentConfig $deploymentConfig
    ) {
        $cacheSetting = $deploymentConfig->get('cache');
        if (isset($cacheSetting['frontend']['default']['backend_options']['server'])) {
            $this->credis = new Credis_Client($cacheSetting['frontend']['default']['backend_options']['server']);
            $this->credis->select(4);
        }
        $this->storeManager = $storeManager;
    }
    public function beforeGetAttributeOptions(\Magento\ConfigurableProduct\Model\AttributeOptionProvider $subject, \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $superAttribute, $productId)
    {
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