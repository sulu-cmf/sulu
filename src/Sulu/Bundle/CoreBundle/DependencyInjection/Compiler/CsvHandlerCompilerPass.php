<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Appends csv-handler to fos-rest view-handler.
 */
class CsvHandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $id = 'fos_rest.view_handler';
        if (!$container->hasDefinition($id)) {
            if (!$container->hasAlias($id)) {
                return;
            }

            $id = (string) $container->getAlias($id);
        }

        $definition = $container->getDefinition($id);
        $definition->addMethodCall(
            'registerHandler',
            ['csv', [new Reference('sulu_core.rest.view_handler.csv'), 'createResponse']]
        );
    }
}
