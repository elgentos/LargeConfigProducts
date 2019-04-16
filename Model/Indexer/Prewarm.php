<?php

namespace Elgentos\LargeConfigProducts\Model\Indexer;

use Elgentos\LargeConfigProducts\Model\PublisherNotifier;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Prewarm implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var ProductCollectionFactory
     */
    public $productCollectionFactory;
    private $storeManager;
    private $messageManager;
    private $output;
    /**
     * @var State
     */
    private $state;

    /**
     * @var PublisherNotifier
     */
    protected $publisherNotifier;

    /**
     * Product constructor.
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $messageManager
     * @param ConsoleOutput $output
     * @param State $state
     * @param PublisherNotifier $publisherNotifier
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ConsoleOutput $output,
        State $state,
        PublisherNotifier $publisherNotifier,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->publisherNotifier = $publisherNotifier;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->output = $output;
        $this->state = $state;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute($productIds)
    {
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        } catch (\Exception $e) {

        }

        if (!is_array($productIds)) {
            /** @var ProductCollection $collection */
            $collection = $this->productCollectionFactory->create();
            $productIds = $collection->addAttributeToFilter('type_id', 'configurable')->getAllIds();
        }

        foreach ($productIds as $productId) {
            $this->publisherNotifier->notify([$productId]);
        }
    }

    public function executeFull()
    {
        $this->execute(null);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
