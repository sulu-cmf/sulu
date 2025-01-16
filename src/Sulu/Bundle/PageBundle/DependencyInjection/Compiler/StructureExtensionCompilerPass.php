<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which adds structure extension to structure manager.
 *
 * @internal this is an internal class which should not be used by a project
 */
class StructureExtensionCompilerPass implements CompilerPassInterface
{
    public const STRUCTURE_MANAGER_ID = 'sulu_page.extension.manager';

    public const STRUCTURE_EXTENSION_TAG = 'sulu.structure.extension';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::STRUCTURE_MANAGER_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::STRUCTURE_MANAGER_ID);
        $taggedServices = $container->findTaggedServiceIds(self::STRUCTURE_EXTENSION_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (isset($attributes['template'])) {
                    $params = [new Reference($id), $attributes['template']];
                } else {
                    $params = [new Reference($id)];
                }

                $definition->addMethodCall('addExtension', $params);
            }
        }
    }
}
