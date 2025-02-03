<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass for collecting services tagged with sulu_media.image.transformation.
 */
class ImageTransformationCompilerPass implements CompilerPassInterface
{
    public const POOL_SERVICE_ID = 'sulu_media.image.transformation_pool';

    public const TAG = 'sulu_media.image.transformation';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::POOL_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::POOL_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $container->getDefinition($id)->setPublic(true);
                $definition->addMethodCall(
                    'add',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
