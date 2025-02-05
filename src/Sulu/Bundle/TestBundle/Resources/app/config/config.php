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
        $loader->import('security-6.yml');
    }

    $loader->import('symfony-6.yml');
};
