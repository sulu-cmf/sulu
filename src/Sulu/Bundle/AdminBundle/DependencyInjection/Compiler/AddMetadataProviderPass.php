<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal this is an internal class which should not be used by a project
 */
class AddMetadataProviderPass implements CompilerPassInterface
{
    public const TAG = 'sulu_admin.metadata_provider';

    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sulu_admin.metadata_provider_registry');

        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $pool->addMethodCall('addMetadataProvider', [$tag['type'], new Reference($id)]);
            }
        }
    }
}
