<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Routing\Loader;

use Sulu\Bundle\DocumentManagerBundle\Routing\Loader\VersionRouteLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

class VersionRouteLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadWithDisabledVersioning()
    {
        $versionRouteLoader = new VersionRouteLoader(false);

        $this->assertCount(0, $versionRouteLoader->load('routing.yml'));
    }

    public function testLoadWithActivatedVersioning()
    {
        $versionRouteLoader = new VersionRouteLoader(true);
        $resolver = $this->prophesize(LoaderResolverInterface::class);
        $loader = $this->prophesize(LoaderInterface::class);
        $loader->load('routing.yml', 'rest')->shouldBeCalled();
        $resolver->resolve('routing.yml', 'rest')->willReturn($loader->reveal());
        $versionRouteLoader->setResolver($resolver->reveal());

        $versionRouteLoader->load('routing.yml');
    }
}
