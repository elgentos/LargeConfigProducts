<?php

namespace Elgentos\LargeConfigProducts\Model\MessageQueues;

use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResourceModel;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\MessageQueue\PublisherInterface;

class Publisher
{
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
     * @param PublisherInterface        $publisher
     * @param ConfigurableResourceModel $configurableResourceModel
     * @param ModuleManager             $moduleManager
     * @param ProductFactory            $productFactory
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
    public function notify(array $productIds)
    {
        foreach ($productIds as $productId) {
            $product = $this->productFactory->create()->load($productId);
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $this->publisher->publish('elgentos.magento.lcp.product.prewarm', $productId);
            } elseif ($product->getTypeId() == 'simple') {
                $parentIds = $this->configurableResourceModel->getParentIdsByChild($productId);
                foreach ($parentIds as $parentId) {
                    $this->publisher->publish('elgentos.magento.lcp.product.prewarm', $parentId);
                }
            }
        }
    }
}
