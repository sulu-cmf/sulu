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

/**
 * Add tagged content types to the content manager.
 *
 * @internal this is an internal class which should not be used by a project
 */
class RegisterContentTypesCompilerPass implements CompilerPassInterface
{
    public const CONTENT_TYPE_TAG = 'sulu.content.type';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu.content.type_manager')) {
            return;
        }

        $contentTypeManager = $container->getDefinition('sulu.content.type_manager');

        $ids = $container->findTaggedServiceIds(self::CONTENT_TYPE_TAG);
        foreach ($ids as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'No "alias" specified for content type with service ID: "%s"',
                        $id
                    )
                );
            }

            $container->getDefinition($id)->setPublic(true);

            $contentTypeManager->addMethodCall(
                'mapAliasToServiceId',
                [$attributes[0]['alias'], $id]
            );
        }
    }
}
