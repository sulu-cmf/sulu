<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DataFixtures;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Console\Output\NullOutput;
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
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private Initializer $initializer
    ) {
    }

    /**
     * Load the given fixture classes.
     */
    public function execute(array $fixtures, $purge = true, $initialize = true, ?OutputInterface $output = null)
    {
        \usort($fixtures, function(DocumentFixtureInterface $fixture1, DocumentFixtureInterface $fixture2) {
            return $fixture1->getOrder() <=> $fixture2->getOrder();
        });

        $output = $output ?: new NullOutput();

        if (true === $initialize) {
            $output->writeln('<comment>Initializing repository</comment>');
            $this->initializer->initialize($output, $purge);
        }

        $output->writeln('<comment>Loading fixtures</comment>');
        foreach ($fixtures as $fixture) {
            $output->writeln(\sprintf(
                ' - %s<info>loading "</info>%s<info>"</info>',
                $fixture instanceof OrderedFixtureInterface ? '[' . $fixture->getOrder() . ']' : '',
                \get_class($fixture)
            ));

            $fixture->load($this->documentManager);
            $this->documentManager->clear();
        }
    }
}
