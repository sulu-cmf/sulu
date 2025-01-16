<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal this is an internal class which should not be used by a project
 */
class DocumentFixturePass implements CompilerPassInterface
{
    public const TAG_NAME = 'sulu.document_manager_fixture';

    /**
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $serviceClass = $definition->getClass();

            if ($serviceClass && \is_subclass_of($serviceClass, ContainerAwareInterface::class)) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '2.1',
                    \sprintf(
                        'Document fixtures with the "%s" are deprecated,' . \PHP_EOL .
                        'use dependency injection for the "%s" service instead.',
                        ContainerAwareInterface::class,
                        $id
                    )
                );

                $definition->addMethodCall('setContainer', [new Reference('service_container')]);
            }
        }
    }
}
