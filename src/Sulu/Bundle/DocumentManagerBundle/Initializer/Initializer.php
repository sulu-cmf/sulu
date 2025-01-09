<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Content repository initializer.
 *
 * Can purge and initialize the content repository using any number of
 * registered *initializers*.
 *
 * Initializers should be designed to be idempotent - they should not do
 * anything to change the state of the repository beyond their initial changes:
 * https://en.wikipedia.org/wiki/Idempotent
 */
class Initializer
{
    /**
     * @param iterable<string, InitializerInterface> $initializerMap
     */
    public function __construct(
        private iterable $initializerMap = []
    ) {
    }

    /**
     * Initialize the content repository, optionally purging it before-hand.
     *
     * @param bool $purge
     *
     * @return void
     */
    public function initialize(?OutputInterface $output = null, $purge = false)
    {
        $output = $output ?: new NullOutput();

        foreach ($this->initializerMap as $serviceId => $initializer) {
            $output->writeln(\sprintf('<comment>%s</>', $serviceId));
            $initializer->initialize($output, $purge);
        }
        $output->write(\PHP_EOL);
        $output->writeln('<comment>*</comment> Legend: [+] Added [*] Updated [-] Purged [ ] No change');
    }
}
