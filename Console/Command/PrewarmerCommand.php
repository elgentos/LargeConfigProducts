<?php
/**
 * Copyright © 2017 Elgentos BV - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace Elgentos\LargeConfigProducts\Console\Command;

use Elgentos\LargeConfigProducts\Model\Prewarmer;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrewarmerCommand extends Command
{
    /**
     * @var RendererInterface
     */
    private $phraseRenderer;
    /**
     * @var State
     */
    private $state;
    /**
     * @var Prewarmer
     */
    private $prewarmer;

    /**
     * PrewarmerCommand constructor.
     *
     * @param RendererInterface $phraseRenderer
     * @param State             $state
     * @param Prewarmer         $prewarmer
     */
    public function __construct(
        RendererInterface $phraseRenderer,
        State $state,
        Prewarmer $prewarmer
    ) {
        parent::__construct();
        $this->phraseRenderer = $phraseRenderer;
        $this->state = $state;
        $this->prewarmer = $prewarmer;
    }

    protected function configure()
    {
        $this->setName('lcp:prewarm');
        $this->setDescription('Prewarm product options JSON for Large Configurable Products');
        $this->addOption('products', 'p', InputOption::VALUE_OPTIONAL, 'Product IDs to prewarm (comma-seperated)');
        $this->addOption('storecodes', 's', InputOption::VALUE_OPTIONAL, 'Storecodes to prewarm (comma-seperated)');
        $this->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force prewarming even if record already exists', false);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_FRONTEND);

        // Set phrase renderer for correct translations, see https://www.atwix.com/magento-2/cli-scripts-translations/
        Phrase::setRenderer($this->phraseRenderer);

        // Echo out instead of using output->writeln due to 'Area code not set' error. This error will not be shown (for some reason) when there has been output sent.
        echo 'Prewarming'.PHP_EOL;

        $productIdsToWarm = [];

        /* Filter products */
        if ($input->getOption('products')) {
            $productIdsToWarm = $input->getOption('products');
            $productIdsToWarm = explode(',', $productIdsToWarm);
            $productIdsToWarm = array_map('trim', $productIdsToWarm);
            $productIdsToWarm = array_filter($productIdsToWarm);
        }

        /** Filter stores */
        $storeCodesToWarm = false;
        if ($input->getOption('storecodes')) {
            $storeCodesToWarm = $input->getOption('storecodes');
            $storeCodesToWarm = explode(',', $storeCodesToWarm);
            $storeCodesToWarm = array_map('trim', $storeCodesToWarm);
            $storeCodesToWarm = array_filter($storeCodesToWarm);
        }

        $force = $input->getOption('force');

        $result = $this->prewarmer->prewarm($productIdsToWarm, $storeCodesToWarm, $force);

        $output->writeln($result);

        $output->writeln('Done prewarming');

        return 0;
    }
}
