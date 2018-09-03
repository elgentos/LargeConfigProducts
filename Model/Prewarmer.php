<?php

namespace Elgentos\LargeConfigProducts\Model;

use Credis_Client;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductTypeConfigurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Prewarmer {
    protected $credis;
    protected $storeManager;
    protected $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    const PREWARM_CURRENT_STORE = 'PREWARM_CURRENT_STORE';
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * PrewarmerCommand constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Emulation $emulation
     * @param DeploymentConfig $deploymentConfig
     * @param Registry $coreRegistry
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Emulation $emulation,
        DeploymentConfig $deploymentConfig,
        Registry $coreRegistry,
        BlockFactory $blockFactory
    ) {
        $this->productRepository = $productRepository;
        $cacheSetting = $deploymentConfig->get('cache');
        if (isset($cacheSetting['frontend']['default']['backend_options']['server'])) {
            $this->credis = new Credis_Client($cacheSetting['frontend']['default']['backend_options']['server']);
            $this->credis->select(4);
        }
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->emulation = $emulation;
        $this->deploymentConfig = $deploymentConfig;
        $this->coreRegistry = $coreRegistry;
        $this->blockFactory = $blockFactory;
    }

    public function prewarm($productIdsToWarm, $storeCodesToWarm, $force)
    {
        if (!$this->credis) {
            throw new \Exception('No Redis configured as default cache frontend!');
        }

        $output = [];

        if (\is_array($productIdsToWarm) && \count($productIdsToWarm) > 0) {
            $this->searchCriteriaBuilder->addFilter('entity_id', $productIdsToWarm, 'in');
        }
        $this->searchCriteriaBuilder->addFilter('type_id', 'configurable');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();

        /** Use the customer-group for guests. It is currently not possible to prewarm the production options with
         * catalog price rules baesd on the customer group as condition.
         * Magento uses the Customer Session to do calculate the CatalogRulePrice and this seems hard to simulate from the CLI
         */
        $customerGroupId = 0;

        $i = 1;
        foreach ($stores as $store) {
            if ($storeCodesToWarm && !in_array($store->getCode(), $storeCodesToWarm)) continue;
            // Use store emulation to let Magento fetch the correct translations for in the JSON object
            $this->emulation->startEnvironmentEmulation($store->getId(), Area::AREA_FRONTEND, true);

            /** We set the current store ID in the Redis database because we need to retrieve it
             *  in AttributeOptionProviderPlugin. We can't use registry for this because of the bug we're actually
             *  'solving' with this. If anyone has a better idea, please PR.
             */
            $this->credis->set(self::PREWARM_CURRENT_STORE, $store->getId());

            /** @var \Magento\Catalog\Api\Data\ProductInterface[] $products */
            $products = $this->productRepository->getList($searchCriteria)->getItems();
            foreach ($products as $product) {
                $cacheKey = 'LCP_PRODUCT_INFO_' . $store->getId() . '_' . $product->getId() . '_' . $customerGroupId;

                if (!$this->credis->exists($cacheKey) || $force) {
                    $output[] = 'Prewarming ' . $product->getSku() . ' for store ' . $store->getCode() . ' (' . $i . '/' . count($products) . ')';
                    $productOptionInfo = $this->getJsonConfig($product);
                    $this->credis->set($cacheKey, $productOptionInfo);
                } else {
                    $output[] = $product->getSku() . ' is already prewarmed for store ' . $store->getCode() . ' (' . $i . '/' . count($products) . ')';
                }
                $i++;
            }
            $this->emulation->stopEnvironmentEmulation();
        }

        return implode(PHP_EOL, $output);
    }



    /**
     * @param $currentProduct
     * @return mixed
     *
     * See original method at Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getJsonConfig
     */
    public function getJsonConfig($currentProduct)
    {
        /* Set product in registry */
        if ($this->coreRegistry->registry('product')) {
            $this->coreRegistry->unregister('product');
        }
        $this->coreRegistry->register('product', $currentProduct);

        /** @var ProductTypeConfigurable $block */
        $block = $this->blockFactory->createBlock(ProductTypeConfigurable::class);

        return $block->getJsonConfig();
    }

}