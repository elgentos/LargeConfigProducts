<?php

namespace Elgentos\LargeConfigProducts\Model;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Rcason\Mq\Api\ConsumerInterface;

class Consumer implements ConsumerInterface
{
    protected $logger;
    /**
     * @var Prewarmer
     */
    private $prewarmer;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param Prewarmer $prewarmer
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Prewarmer $prewarmer
    ) {
        $this->logger = $logger;
        $this->prewarmer = $prewarmer;
    }

    /**
     * {@inheritdoc}
     */
    public function process($productId)
    {
        echo sprintf('Processing %s..', $productId) . PHP_EOL;

        try {
            $this->prewarmer->prewarm([$productId], false, true);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}