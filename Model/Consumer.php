<?php

namespace Elgentos\LargeConfigProducts\Model;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Rcason\Mq\Api\ConsumerInterface;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class Consumer implements ConsumerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Prewarmer
     */
    private $prewarmer;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param Prewarmer $prewarmer
     * @param Process $process
     */
    public function __construct(
        LoggerInterface $logger,
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
            $process = new Process(sprintf('php bin/magento lcp:prewarm -p %s --force=true', $productId));
            $process->run();
            echo $process->getOutput();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}