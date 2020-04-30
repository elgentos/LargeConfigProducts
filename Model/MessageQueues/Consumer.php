<?php

namespace Elgentos\LargeConfigProducts\Model\MessageQueues;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Mtf\Config\FileResolver\ScopeConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Consumer
{
    const PREWARM_PROCESS_TIMEOUT = 300;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Prewarmer
     */
    private $prewarmer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param Prewarmer                $prewarmer
     * @param Process                  $process
     * @param ScopeConfigInterface     $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Prewarmer $prewarmer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->prewarmer = $prewarmer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function processMessage(string $productId)
    {
        echo sprintf('Processing %s..', $productId).PHP_EOL;

        $absolutePath = $this->scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/absolute_path');

        if (!$absolutePath) {
            $this->logger->info('[Elgentos_LargeConfigProducts] Could not prewarm through message queue; no absolute path is set in LCP configuration.');

            return;
        }

        // Strip trailing slash
        if (substr($absolutePath, -1) == '/') {
            $absolutePath = substr($absolutePath, 0, -1);
        }

        try {
            $process = new Process(sprintf('php %s/bin/magento lcp:prewarm -p %s --force=true', $absolutePath, $productId), null, null, null, self::PREWARM_PROCESS_TIMEOUT);
            $process->run();
            echo $process->getOutput();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
