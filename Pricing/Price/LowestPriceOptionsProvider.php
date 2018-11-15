<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 15-11-18
 * Time: 10:58
 */

namespace Elgentos\LargeConfigProducts\Pricing\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Store\Model\StoreManagerInterface;
use Credis_Client;

class LowestPriceOptionsProvider extends \Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Credis_Client
     */
    protected $credis;
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LinkedProductSelectBuilderInterface
     */
    private $linkedProductSelectBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Key is product id. Value is array of prepared linked products
     *
     * @var array
     */
    private $linkedProductMap;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LinkedProductSelectBuilderInterface $linkedProductSelectBuilder
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LinkedProductSelectBuilderInterface $linkedProductSelectBuilder,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resourceConnection;
        $this->linkedProductSelectBuilder = $linkedProductSelectBuilder;
        $this->collectionFactory = $collectionFactory;

        $this->credis = new Credis_Client(
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_host') ?? 'localhost',
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_port') ?? 6379,
            null,
            '',
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_db_index') ?? 4
        );
        $this->storeManager = $storeManager;
    }


    /**
     * {@inheritdoc}
     */
    public function getProducts(ProductInterface $product)
    {
        /**
         * The currentStoreId that is being set in the emulation in PrewarmerCommand is somehow lost in the call
         * stack. This plugin uses the store ID found in the Redis DB to re-set the current store so the prices
         * are retrieved correctly.
         */
        if (PHP_SAPI == 'cli') {
            $prewarmCurrentStore = $this->credis->get(Prewarmer::PREWARM_CURRENT_STORE);
            if ($prewarmCurrentStore) {
                $this->storeManager->setCurrentStore($prewarmCurrentStore);
            }
        }

        $productIds = $this->resource->getConnection()->fetchCol(
            '(' . implode(') UNION (', $this->linkedProductSelectBuilder->build($product->getId())) . ')'
        );

        return $this->linkedProductMap[$product->getId()] = $this->collectionFactory->create()
            ->addAttributeToSelect(
                ['price', 'special_price', 'special_from_date', 'special_to_date', 'tax_class_id']
            )
            ->addIdFilter($productIds)
            ->getItems();
    }

}