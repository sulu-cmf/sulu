<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures\fixtures;

use Massive\Bundle\BuildBundle\ContainerAwareInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerFixture implements DocumentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface|null
     */
    public $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(DocumentManager $documentManager): void
    {
    }

    public function getOrder()
    {
        return 30;
    }
}
