<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This CompilerPass adds deprecated parameters and definitions to the container.
 * This is necessary to avoid BC breaks since these values are set dynamically by the PersistenceBundle.
 *
 * @deprecated This is here only for BC reasons and will be removed in 2.0
 *
 * @internal this is an internal class which should not be used by a project
 */
class DeprecationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter('sulu.model.category.class')) {
            $container->setParameter(
                'sulu_category.entity.category',
                $container->getParameter('sulu.model.category.class')
            );
        }

        if ($container->hasParameter('sulu.model.keyword.class')) {
            $container->setParameter(
                'sulu_category.entity.keyword',
                $container->getParameter('sulu.model.keyword.class')
            );
        }

        if ($container->hasDefinition('sulu.repository.category')) {
            $container->setAlias('sulu_category.category_repository', 'sulu.repository.category');
        }

        if ($container->hasDefinition('sulu.repository.keyword')) {
            $container->setAlias('sulu_category.keyword_repository', 'sulu.repository.keyword');
        }
    }
}
