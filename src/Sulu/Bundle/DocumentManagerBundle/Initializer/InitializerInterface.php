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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializers are called when the repository is initialized (or reinitialized).
 *
 * They should create any necessary nodes/documents in the content repository and they MUST NOT remove or destructively
 * modify existing nodes/documents when the purge flag is set to false.
 */
interface InitializerInterface
{
    /**
     * @return void
     */
    public function initialize(OutputInterface $output, $purge = false);
}
