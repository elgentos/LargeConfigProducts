<?php

namespace Elgentos\LargeConfigProducts\Observer;

use Elgentos\LargeConfigProducts\Model\PublisherNotifier;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * ProductSaveAfter constructor.
     * @param PublisherNotifier $publisherNotifier
     */
    public function __construct(PublisherNotifier $publisherNotifier) {
        $this->publisherNotifier = $publisherNotifier;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->publisherNotifier->notify([$observer->getProduct()->getId()]);
    }
}