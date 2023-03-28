<?php

namespace Elgentos\LargeConfigProducts\Model\MessageQueues;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Elgentos\LargeConfigProducts\Cache\CredisClientFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Mtf\Config\FileResolver\ScopeConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Consumer
{
    const PREWARM_PROCESS_TIMEOUT = 3600;
    const PREWARM_THROTTLE_TTL = 60;
    protected $credis;

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
     * @param CredisClientFactory      $credisClientFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Prewarmer $prewarmer,
        ScopeConfigInterface $scopeConfig,
        CredisClientFactory $credisClientFactory
    ) {
        $this->logger = $logger;
        $this->prewarmer = $prewarmer;
        $this->scopeConfig = $scopeConfig;
        $this->credis = $credisClientFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function processMessage(string $productId)
    {
        
        $absolutePath = $this->scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/absolute_path');

        if (!$absolutePath) {
            $this->logger->info('[Elgentos_LargeConfigProducts] Could not prewarm through message queue; no absolute path is set in LCP configuration.');

            return;
        }

        // Strip trailing slash
        if (substr($absolutePath, -1) == '/') {
            $absolutePath = substr($absolutePath, 0, -1);
        }

        $processCommand=sprintf('php %s/bin/magento lcp:prewarm -p %s --force=true', $absolutePath, $productId);
        echo 'Starting process : '. $processCommand.PHP_EOL;

        try {

            $cacheKey='LCP_PRODUCT_X_'.$productId;

            // consumer throttling - prevents boring situations
            if ($this->credis->exists($cacheKey))
            {
                echo 'Skipping - last process < '.self::PREWARM_THROTTLE_TTL.'s'.PHP_EOL;

            } else {

                $process = Process::fromShellCommandline($processCommand);
                $process->setTimeout(self::PREWARM_PROCESS_TIMEOUT);
                
                $process->run(function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        echo 'ERR > '.$buffer;
                    } else {
                        echo $buffer;
                    }
                });

                $this->credis->set($cacheKey, (new \DateTime())->format('d-m-Y h:i:s'));
                $this->credis->expire($cacheKey, self::PREWARM_THROTTLE_TTL);
                
            }

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
