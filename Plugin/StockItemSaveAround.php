<?php

namespace Elgentos\LargeConfigProducts\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;

class StockItemSaveAround
{
    private $indexer;

    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('elgentos_lcp_prewarm');
    }

    public function aroundSave(
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $stockItemModel,
        \Closure $proceed,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ) {
        $stockItemModel->addCommitCallback(function () use ($stockItem) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($stockItem->getProductId());
            }
        });

        return $proceed($stockItem);
    }
}
