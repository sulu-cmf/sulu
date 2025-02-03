<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Read versions from composer.lock and composer.json.
 */
class SuluVersionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $dir = \realpath($container->getParameter('kernel.project_dir'));

        $container->setParameter('sulu.version', $this->getSuluVersion($dir));
        $container->setParameter('app.version', $this->getAppVersion($dir));
    }

    /**
     * Read composer.lock file and return version of sulu.
     *
     * @param string $dir
     *
     * @return string
     */
    private function getSuluVersion($dir)
    {
        $version = '_._._';

        /** @var SplFileInfo $composerFile */
        $composerFile = new SplFileInfo($dir . '/composer.lock', '', '');
        if (!$composerFile->isFile()) {
            return $version;
        }

        $composer = \json_decode($composerFile->getContents(), true);
        foreach ($composer['packages'] as $package) {
            if ('sulu/sulu' === $package['name']) {
                return $package['version'];
            }
        }

        return $version;
    }

    /**
     * Read composer.json file and return version of app.
     *
     * @param string $dir
     *
     * @return string
     */
    private function getAppVersion($dir)
    {
        $version = null;

        /** @var SplFileInfo $composerFile */
        $composerFile = new SplFileInfo($dir . '/composer.json', '', '');
        if (!$composerFile->isFile()) {
            return $version;
        }

        $composerJson = \json_decode($composerFile->getContents(), true);
        if (!\array_key_exists('version', $composerJson)) {
            return $version;
        }

        return $composerJson['version'];
    }
}
