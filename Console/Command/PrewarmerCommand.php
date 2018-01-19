<?php
/**
 * Copyright © 2017 Elgentos BV - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace Elgentos\LargeConfigProducts\Console\Command;

use Credis_Client;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrewarmerCommand extends Command
{
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $_coreRegistry;
    /**
     * @var BlockFactory
     */
    private $blockFactory;
    /**
     * @var Credis_Client
     */
    private $credis;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var RendererInterface
     */
    private $phraseRenderer;

    /**
     * PrewarmerCommand constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Registry $coreRegistry
     * @param DeploymentConfig $deploymentConfig
     * @param BlockFactory $blockFactory
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param RendererInterface $phraseRenderer
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Registry $coreRegistry,
        DeploymentConfig $deploymentConfig,
        BlockFactory $blockFactory,
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        RendererInterface $phraseRenderer
    ) {
        parent::__construct();
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_coreRegistry = $coreRegistry;
        $this->blockFactory = $blockFactory;
        $cacheSetting = $deploymentConfig->get('cache');
        if (isset($cacheSetting['frontend']['default']['backend_options']['server'])) {
            $this->credis = new Credis_Client($cacheSetting['frontend']['default']['backend_options']['server']);
            $this->credis->select(4);
        }
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->phraseRenderer = $phraseRenderer;
    }
    /**
     *
     */
    protected function configure()
    {
        $this->setName('lcp:prewarm');
        $this->setDescription('Prewarm product options JSON for Large Configurable Products');
        $this->addOption('products', 'p', InputOption::VALUE_OPTIONAL, 'Product ID(s) to prewarm (comma-seperated)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->credis) {
            throw new \Exception('No Redis configured as default cache frontend!');
        }

        // Set phrase renderer for correct translations, see https://www.atwix.com/magento-2/cli-scripts-translations/
        Phrase::setRenderer($this->phraseRenderer);

        $output->writeln('Prewarming...');

        if ($input->getOption('products')) {
            $productIdsToWarm = $input->getOption('products');
            $productIdsToWarm = explode(',', $productIdsToWarm);
            $productIdsToWarm = array_map('trim', $productIdsToWarm);
            $productIdsToWarm = array_filter($productIdsToWarm);
            $this->searchCriteriaBuilder->addFilter('entity_id', $productIdsToWarm, 'in');
        }
        $this->searchCriteriaBuilder->addFilter('type_id', 'configurable');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var \Magento\Catalog\Api\Data\ProductInterface[] $products */
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();

        $i = 1;
        foreach ($products as $product) {
            foreach ($stores as $store) {
                // Use store emulation to let Magento fetch the correct translations for in the JSON object
                $this->emulation->startEnvironmentEmulation($store->getId(), Area::AREA_FRONTEND, true);
                $cacheKey = 'LCP_PRODUCT_INFO_' . $store->getId() . '_' . $product->getId();

                if (!$this->credis->exists($cacheKey)) {
                    $output->writeln('Prewarming ' . $product->getSku() . ' for store ' . $store->getCode() . ' (' . $i . '/' . count($products) . ')');
                    $productOptionInfo = $this->getJsonConfig($product);
                    $this->credis->set($cacheKey, $productOptionInfo);
                } else {
                    $output->writeln($product->getSku() . ' is already prewarmed for store ' . $store->getCode() . ' (' . $i . '/' . count($products) . ')');
                }
                $this->emulation->stopEnvironmentEmulation();
            }
            $i++;
        }

        $output->writeln('Done prewarming');
    }


    /**
     * @param $currentProduct
     * @return mixed
     *
     * See original method at Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getJsonConfig
     */
    public function getJsonConfig($currentProduct)
    {
        if ($this->_coreRegistry->registry('product')) {
            $this->_coreRegistry->unregister('product');
        }
        $this->_coreRegistry->register('product', $currentProduct);

        $block = $this->blockFactory->createBlock('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable');

        return $block->getJsonConfig();
    }

}