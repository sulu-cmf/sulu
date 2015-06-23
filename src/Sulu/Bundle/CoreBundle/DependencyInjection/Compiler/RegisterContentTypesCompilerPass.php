<?php
/*
 * This file is part of the Sulu CMS.
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
 * Add tagged content types to the content manager.
 */
class RegisterContentTypesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu.content.type_manager')) {
            return;
        }

        $contentTypeManager = $container->getDefinition('sulu.content.type_manager');

        $ids = $container->findTaggedServiceIds('sulu.content.type');
        foreach ($ids as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'No "alias" specified for content type with service ID: "%s"',
                        $id
                    )
                );
            }

            $contentTypeManager->addMethodCall(
                'mapAliasToServiceId',
                array($attributes[0]['alias'], $id)
            );
        }
    }
}
