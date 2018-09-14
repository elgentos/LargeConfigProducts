<?php

namespace Elgentos\LargeConfigProducts\Observer;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResourceModel;
use Magento\Framework\Event\ObserverInterface;
use Rcason\Mq\Api\PublisherInterface;
use Magento\Framework\Module\Manager as ModuleManager;

class ProductSaveAfter implements ObserverInterface
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
     * @param PublisherInterface $publisher
     * @param ConfigurableResourceModel $configurableResourceModel
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        PublisherInterface $publisher,
        ConfigurableResourceModel $configurableResourceModel,
        ModuleManager $moduleManager
    ) {
        $this->publisher = $publisher;
        $this->configurableResourceModel = $configurableResourceModel;
        $this->moduleManager = $moduleManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($this->moduleManager->isEnabled('Rcason_Mq')) {
            if ($observer->getProduct()->getTypeId() == Configurable::TYPE_CODE) {
                $this->publisher->publish('lcp.product.prewarm', $observer->getProduct()->getId());
            } elseif ($observer->getProduct()->getTypeId() == 'simple') {
                $parentIds = $this->configurableResourceModel->getParentIdsByChild($observer->getProduct()->getId());
                foreach ($parentIds as $parentId) {
                    $this->publisher->publish('lcp.product.prewarm', $parentId);
                }
            }
        }
    }
}