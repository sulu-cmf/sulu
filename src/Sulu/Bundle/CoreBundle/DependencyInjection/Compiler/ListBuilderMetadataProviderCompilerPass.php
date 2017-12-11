<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Combines all existing metadata-provider.
 */
class ListBuilderMetadataProviderCompilerPass implements CompilerPassInterface
{
    const CHAIN_PROVIDER_ID = 'sulu_core.list_builder.metadata.provider.chain';

    const PROVIDER_TAG_ID = 'sulu.list-builder.metadata.provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CHAIN_PROVIDER_ID)) {
            return;
        }

        $chainProvider = $container->getDefinition(self::CHAIN_PROVIDER_ID);
        $ids = $container->findTaggedServiceIds(self::PROVIDER_TAG_ID);

        $chainProvider->replaceArgument(
            0,
            array_map(
                function ($id) {
                    return new Reference($id);
                },
                array_keys($ids)
            )
        );
    }
}
