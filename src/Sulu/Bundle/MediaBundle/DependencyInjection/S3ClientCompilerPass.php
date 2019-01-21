<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
        if (Configuration::STORAGE_S3 !== $container->getParameter('sulu_media.media.storage')) {
            return;
        }

        $s3Client = $container->getDefinition('sulu_media.storage.aws_s3.client');
        $additionalArguments = $container->getParameter('sulu_media.media.storage.aws_s3.arguments');

        $argument = array_merge($s3Client->getArgument(0), $additionalArguments);
        $s3Client->setArgument(0, array_filter($argument));
    }
}
