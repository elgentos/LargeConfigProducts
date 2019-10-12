<?php

declare(strict_types=1);

namespace Elgentos\LargeConfigProducts\Cache;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;

class CredisClientFactory
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(DeploymentConfig $deploymentConfig, ScopeConfigInterface $scopeConfig)
    {
        $this->deploymentConfig = $deploymentConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function create(): \Credis_Client
    {
        $cacheSetting = $this->deploymentConfig->get('cache');

        $timeout = null;
        $persistent = '';
        if (isset($cacheSetting['frontend']['elgentos_largeconfigproducts']['backend_options'])) {
            $backendOptions = $cacheSetting['frontend']['elgentos_largeconfigproducts']['backend_options'];

            $server = $backendOptions['server'];
            $database = $backendOptions['database'];
            $port = $backendOptions['port'];
        } else {
            $server = $this->scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_host') ?? 'localhost';

            $port = $this->scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_port') ?? 6379;
            $database = $this->scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_db_index') ?? 4;
        }

        return new \Credis_Client($server, $port, $timeout, $persistent, $database);
    }
}
