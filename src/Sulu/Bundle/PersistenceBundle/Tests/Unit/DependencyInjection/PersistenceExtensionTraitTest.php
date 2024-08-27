<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Sulu\Bundle\PersistenceBundle\Tests\Unit\Fixture\DependencyInjection\UsingPersistenceExtensionTrait;
use Symfony\Component\DependencyInjection\Reference;

class PersistenceExtensionTraitTest extends AbstractContainerBuilderTestCase
{
    public function testPersistenceExtensionTrait(): void
    {
        $repository = new class() {
        };

        $extension = new UsingPersistenceExtensionTrait();
        $extension->load(
            [
                'objects' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => \get_class($repository),
                    ],
                ],
            ],
            $this->container
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.foo.class',
            'Sulu\Component\Persistence\Model\Foo'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.bar.class',
            'Sulu\Component\Persistence\Model\Bar'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.repository.bar.class',
            \get_class($repository)
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.persistence.objects',
            [
                'sulu' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => \get_class($repository),
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasService(
            'sulu.repository.bar',
            \get_class($repository)
        );
    }

    public function testPersistenceExtensionTraitWithAccessControlQueryEnhancer(): void
    {
        $repository = new class() {
            public function setAccessControlQueryEnhancer($accessControlQueryEnhancer): void
            {
            }
        };

        $extension = new UsingPersistenceExtensionTrait();
        $extension->load(
            [
                'objects' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => \get_class($repository),
                    ],
                ],
            ],
            $this->container
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.foo.class',
            'Sulu\Component\Persistence\Model\Foo'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.bar.class',
            'Sulu\Component\Persistence\Model\Bar'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.repository.bar.class',
            \get_class($repository)
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.persistence.objects',
            [
                'sulu' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => \get_class($repository),
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasService(
            'sulu.repository.bar',
            \get_class($repository)
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu.repository.bar',
            'setAccessControlQueryEnhancer',
            [new Reference('sulu_security.access_control_query_enhancer')]
        );
    }
}
