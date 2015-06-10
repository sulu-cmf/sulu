<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InitializerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_document_manager.initializer')) {
            return;
        }

        $initializerDef = $container->getDefinition('sulu_document_manager.initializer');

        $ids = $container->findTaggedServiceIds('sulu_document_manager.initializer');
        $map = array();

        foreach ($ids as $id => $attributes) {
            $priority = 0;
            if (isset($attributes[0]['priotity'])) {
                $priority = $attributes[0]['priority'];
            }
            $map[$id] = $priority;
        }

        $initializerDef->replaceArgument(1, $map);
    }
}

