<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.yml');

        if ('jackrabbit' === getenv('SULU_PHPCR_TRANSPORT')) {
            $loader->load(__DIR__ . '/config/versioning.yml');
        }

        if (class_exists('Sulu\Bundle\SearchBundle\SuluSearchBundle')) {
            $loader->load(__DIR__ . '/config/search.yml');
        }
    }
}
