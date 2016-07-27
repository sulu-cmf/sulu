<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterContentTypesCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all export able content types.
 */
class ContentExportCompilerPass implements CompilerPassInterface
{
    const CONTENT_EXPORT_SERVICE_ID = 'sulu_content.export.manager';
    const STRUCTURE_EXTENSION_TAG = 'sulu.content.export';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CONTENT_EXPORT_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::CONTENT_EXPORT_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::STRUCTURE_EXTENSION_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            $contentTypeServiceDefinition = $container->getDefinition($id);
            $tags = $contentTypeServiceDefinition->getTag(RegisterContentTypesCompilerPass::CONTENT_TYPE_TAG);
            foreach ($tags as $tag) {
                $contentTypeName = $tag['alias'];
                foreach ($tagAttributes as $attributes) {
                    $format = $attributes['format'];
                    unset($attributes['format']);
                    $definition->addMethodCall('add', [$contentTypeName, $format, $attributes]);
                }
            }
        }
    }
}
