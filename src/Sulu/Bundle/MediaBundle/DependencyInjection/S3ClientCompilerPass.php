<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Prepares arguments for S3-Client.
 */
class S3ClientCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        return;
        if (Configuration::STORAGE_S3 !== $container->getParameter('sulu_media.media.storage')) {
            return;
        }

        $s3Client = $container->getDefinition('sulu_media.storage.s3.client');
        /** @var mixed[] $additionalArguments */
        $additionalArguments = $container->getParameter('sulu_media.media.storage.s3.arguments');

        $argument = \array_merge($s3Client->getArgument(0), $additionalArguments);
        $s3Client->setArgument(0, \array_filter($argument));
    }
}
