<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

return static function(PhpFileLoader $loader, ContainerBuilder $container) {
    $filesystem = new Filesystem();

    $context = $container->getParameter('sulu.context');
    $path = __DIR__ . \DIRECTORY_SEPARATOR;
    if (!$filesystem->exists($path . 'parameters.yml')) {
        $filesystem->copy($path . 'parameters.yml.dist', $path . 'parameters.yml');
    }
    $loader->import('parameters.yml');
    $loader->import('context_' . $context . '.yml');

    if ('admin' === $context) {
        if (\class_exists(Symfony\Bundle\SecurityBundle\Command\UserPasswordEncoderCommand::class)) { // detect Symfony <= 5.4
            $loader->import('security-5-4.yml');
        } else {
            $loader->import('security-6.yml');
        }
    }

    if (\version_compare(Kernel::VERSION, '6.0.0', '>=')) {
        $loader->import('symfony-6.yml');
    } else {
        $loader->import('symfony-5-4.yml');
    }
};
