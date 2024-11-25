<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\SuluContentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluContentBundleTest extends TestCase
{
    protected function getContentBundle(): SuluContentBundle
    {
        return new SuluContentBundle();
    }

    public function testPersistenceCompilerPass(): void
    {
        $bundle = $this->getContentBundle();
        $containerBuilder = new ContainerBuilder();

        $passConfig = $containerBuilder->getCompiler()->getPassConfig();
        $beforeCount = \count($passConfig->getPasses());

        $bundle->build($containerBuilder);
        $passConfig = $containerBuilder->getCompiler()->getPassConfig();

        $this->assertSame(
            1,
            \count($passConfig->getPasses()) - $beforeCount
        );
    }
}
