<?php

namespace Sulu\Bundle\DocumentManagerBundle\DataFixtures;

use Symfony\Component\Console\Output\NullOutput;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handles the process of loading fixtures.
 *
 * Given a set of fixture instances this class will (optionall)
 * purge and initialize the content repository before executing
 * the given fixture instances.
 */
class DocumentExecutor
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var Initializer
     */
    private $initializer;

    /**
     * @param DocumentManager $documentManager
     * @param NodeManager $nodeManager
     * @param Initializer $initializer
     */
    public function __construct(
        DocumentManager $documentManager,
        NodeManager $nodeManager,
        Initializer $initializer
    )
    {
        $this->documentManager = $documentManager;
        $this->nodeManager = $nodeManager;
        $this->initializer = $initializer;
    }

    /**
     * Load the given fixture classes.
     *
     * @param array $fixtures
     * @param mixed $purge
     * @param mixed $initialize
     * @param OutputInterface $output
     */
    public function execute(array $fixtures, $purge = true, $initialize = true, OutputInterface $output = null)
    {
        $output = $output ? : new NullOutput();

        if (true === $purge) {
            $output->writeln('<comment>Purging workspace</comment>');
            $this->nodeManager->purgeWorkspace();
            $this->nodeManager->save();
        }

        if (true === $initialize) {
            $output->writeln('<comment>Initializing repository</comment>');
            $this->initializer->initialize($output);
        }

        $output->writeln('<comment>Loading fixtures</comment>');
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf(
                ' - %s<info>loading "</info>%s<info>"</info>',
                $fixture instanceof OrderedFixtureInterface ? '[' . $fixture->getOrder() . ']' : '',
                get_class($fixture)
            ));

            $fixture->load($this->documentManager);
            $this->documentManager->clear();
        }
    }
}
