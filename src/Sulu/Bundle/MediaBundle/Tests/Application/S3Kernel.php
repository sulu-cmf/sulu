<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Application;

use Sulu\Bundle\MediaBundle\Tests\Functional\Mock\S3AdapterMock;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal this is an internal class which should not be used by a project
 */
class S3Kernel extends Kernel implements CompilerPassInterface
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config_s3.yml');
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . $this->getContext() . '_s3' . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('sulu_media.storage.s3')->setPublic(true);
        $container->getDefinition('sulu_media.storage.s3.adapter')
            ->setClass(S3AdapterMock::class)
            ->setPublic(true);
    }
}
