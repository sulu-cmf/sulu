<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes all the services, which are not registered for the current context.
 * Register your services for one context using the sulu.context tag.
 * @package Sulu\Bundle\CoreBundle\DependencyInjection\Compiler
 */
class RemoveForeignContextServicesPass implements CompilerPassInterface
{
    const SULU_CONTEXT_TAG = 'sulu.context';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('sulu.context')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::SULU_CONTEXT_TAG);
        $context = $container->getParameter('sulu.context');

        foreach ($taggedServices as $id => $attributes) {
            if ($attributes[0]['context'] != $context) {
                $container->removeDefinition($id);
            }
        }
    }
}
