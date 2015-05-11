<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializers are called when the repository is initialized (or reinitialized).
 *
 * They should create any necessary nodes/documents in the content repository and
 * they MUST NOT remove or destructively modify existing nodes/documents.
 */
interface InitializerInterface
{
    public function initialize(OutputInterface $output);
}
