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

use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterContentTypesCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all export able content types.
 */
class ContentExportCompilerPass implements CompilerPassInterface
{
    public const CONTENT_EXPORT_SERVICE_ID = 'sulu_page.export.manager';

    public const STRUCTURE_EXTENSION_TAG = 'sulu.content.export';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::CONTENT_EXPORT_SERVICE_ID)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::STRUCTURE_EXTENSION_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            $this->processTagAttributes($container, $id, $tagAttributes);
        }
    }

    private function processTagAttributes(ContainerBuilder $container, $id, $tagAttributes)
    {
        $definition = $container->getDefinition(self::CONTENT_EXPORT_SERVICE_ID);
        $contentTypeServiceDefinition = $container->getDefinition($id);
        $tags = $contentTypeServiceDefinition->getTag(RegisterContentTypesCompilerPass::CONTENT_TYPE_TAG);

        foreach ($tags as $tag) {
            $contentTypeName = $tag['alias'];
            $this->exportAttribute($tagAttributes, $contentTypeName, $definition);
        }
    }

    private function exportAttribute($tagAttributes, $contentTypeName, $definition)
    {
        foreach ($tagAttributes as $attributes) {
            $format = $attributes['format'];
            unset($attributes['format']);
            $definition->addMethodCall('add', [$contentTypeName, $format, $attributes]);
        }
    }
}
