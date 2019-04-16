<?php

namespace Elgentos\LargeConfigProducts\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

class ProductSaveAfter implements ObserverInterface
{
    private $indexer;

    /**
     * ProductSaveAfter constructor.
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('elgentos_lcp_prewarm');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexRow($observer->getProduct()->getId());
        }
    }
}