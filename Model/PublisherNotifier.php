<?php

namespace Elgentos\LargeConfigProducts\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResourceModel;
use Rcason\Mq\Api\PublisherInterface;
use Magento\Framework\Module\Manager as ModuleManager;

class PublisherNotifier {

    /**
     * @var PublisherInterface
     */
    private $publisher;
    /**
     * @var ConfigurableResourceModel
     */
    private $configurableResourceModel;
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param PublisherInterface $publisher
     * @param ConfigurableResourceModel $configurableResourceModel
     * @param ModuleManager $moduleManager
     * @param ProductFactory $productFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        ConfigurableResourceModel $configurableResourceModel,
        ModuleManager $moduleManager,
        ProductFactory $productFactory
    ) {
        $this->publisher = $publisher;
        $this->configurableResourceModel = $configurableResourceModel;
        $this->moduleManager = $moduleManager;
        $this->productFactory = $productFactory;
    }

    /**
     * @param $productIds array
     */
    public function notify(array $productIds) {
        if ($this->moduleManager->isEnabled('Rcason_Mq')) {
            foreach ($productIds as $productId) {
                $product = $this->productFactory->create()->load($productId);
                if ($product->getTypeId() == Configurable::TYPE_CODE) {
                    $this->publisher->publish('lcp.product.prewarm', $productId);
                } elseif ($product->getTypeId() == 'simple') {
                    $parentIds = $this->configurableResourceModel->getParentIdsByChild($productId);
                    foreach ($parentIds as $parentId) {
                        $this->publisher->publish('lcp.product.prewarm', $parentId);
                    }
                }
            }
        }
    }

}