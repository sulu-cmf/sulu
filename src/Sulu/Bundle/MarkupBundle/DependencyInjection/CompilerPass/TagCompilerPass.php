<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all tags for markup.
 *
 * @internal this is an internal class which should not be used by a project
 */
class TagCompilerPass implements CompilerPassInterface
{
    public const SERVICE_ID = 'sulu_markup.tag.registry';

    public const TAG_NAME = 'sulu_markup.tag';

    public const NAMESPACE_ATTRIBUTE = 'namespace';

    public const TAG_ATTRIBUTE = 'tag';

    public const TYPE_ATTRIBUTE = 'type';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $references = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = $attributes[self::TYPE_ATTRIBUTE];
                $namespace = \array_key_exists(self::NAMESPACE_ATTRIBUTE, $attributes)
                    ? $attributes[self::NAMESPACE_ATTRIBUTE] : 'sulu';
                $tag = $attributes[self::TAG_ATTRIBUTE];

                if (!\array_key_exists($type, $references)) {
                    $references[$type] = [];
                }

                $references[$type][$namespace][$tag] = new Reference($id);
            }
        }

        if (0 === \count($references)) {
            return;
        }

        $container->getDefinition(self::SERVICE_ID)->replaceArgument(0, $references);
    }
}
