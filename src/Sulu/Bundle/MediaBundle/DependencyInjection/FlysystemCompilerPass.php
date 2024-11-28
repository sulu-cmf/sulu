<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class FlysystemCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $mediaStorage = $container->getDefinition('sulu_media.storage');
        $adapterDefinition = $container->getDefinition($mediaStorage->getArgument(1)->__toString());

        if (LocalFilesystemAdapter::class === $adapterDefinition->getClass()) {
            $mediaStorage->setArgument(3, $adapterDefinition->getArgument(0));
        }
    }
}
