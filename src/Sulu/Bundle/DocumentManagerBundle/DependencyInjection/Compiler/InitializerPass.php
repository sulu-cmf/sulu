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
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @deprecated Since 2.6 use tagged_iterator instead
 */
class InitializerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_document_manager.initializer')) {
            return;
        }

        $initializerDef = $container->getDefinition('sulu_document_manager.initializer');

        $ids = $container->findTaggedServiceIds('sulu_document_manager.initializer');
        $map = [];

        foreach ($ids as $id => $attributes) {
            $priority = 0;
            if (isset($attributes[0]['priority'])) {
                $priority = $attributes[0]['priority'];
            }
            $container->getDefinition($id)->setPublic(true);
            $map[$id] = $priority;
        }

        $initializerDef->replaceArgument(1, $map);
    }
}
