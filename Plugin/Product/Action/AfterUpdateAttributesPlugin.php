<?php

namespace Elgentos\LargeConfigProducts\Plugin\Product\Action;

use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Indexer\IndexerRegistry;

class AfterUpdateAttributesPlugin
{
    private $indexer;

    /**
     * AfterUpdateAttributesPlugin constructor.
     *
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('elgentos_lcp_prewarm');
    }

    /**
     * @param ProductAction $subject
     * @param ProductAction $action
     * @param $productIds
     * @param $attrData
     * @param $storeId
     *
     * @return ProductAction
     */
    public function afterUpdateAttributes(
        ProductAction $subject,
        ProductAction $action,
        $productIds,
        $attrData,
        $storeId
    ) {
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList($productIds);
        }

        return $action;
    }
}
