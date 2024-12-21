<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RemoveForeignContextServicesPassTest extends AbstractCompilerPassTestCase
{
    public static function provideWebsiteServices()
    {
        return [
            [
                [
                    ['service' => 'service 1', 'context' => 'website', 'included' => true],
                    ['service' => 'service 2', 'context' => 'website', 'included' => true],
                    ['service' => 'service 3', 'context' => 'admin', 'included' => false],
                ],
            ],
        ];
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RemoveForeignContextServicesPass());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideWebsiteServices')]
    public function testRemoveWebsiteServices($services): void
    {
        $this->setParameter('sulu.context', 'website');

        foreach ($services as $service) {
            $definition = new Definition();
            $definition->addTag('sulu.context', ['context' => $service['context']]);

            $this->setDefinition($service['service'], $definition);
        }

        $this->compile();

        foreach ($services as $service) {
            if ($service['included']) {
                $this->assertContainerBuilderHasService($service['service']);
            } else {
                $this->assertContainerBuilderNotHasService($service['service']);
            }
        }
    }
}
