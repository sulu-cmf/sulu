<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all data provider for smart content.
 */
class SmartContentDataProviderCompilerPass implements CompilerPassInterface
{
    const POOL_SERVICE_ID = 'sulu_content.smart_content.data_provider_pool';
    const STRUCTURE_EXTENSION_TAG = 'sulu.smart_content.data_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::POOL_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::POOL_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::STRUCTURE_EXTENSION_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall('add', [$attributes['alias'], new Reference($id)]);
            }
        }
    }
}
