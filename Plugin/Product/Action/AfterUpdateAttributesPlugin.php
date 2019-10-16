<?php

namespace Elgentos\LargeConfigProducts\Plugin\Product\Action;

use Elgentos\LargeConfigProducts\Model\PublisherNotifier;
use Magento\Catalog\Model\Product\Action as ProductAction;

class AfterUpdateAttributesPlugin
{
    /**
     * AfterUpdateAttributesPlugin constructor.
     *
     * @param PublisherNotifier $publisherNotifier
     */
    public function __construct(PublisherNotifier $publisherNotifier)
    {
        $this->publisherNotifier = $publisherNotifier;
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
        $this->publisherNotifier->notify($productIds);

        return $action;
    }
}
