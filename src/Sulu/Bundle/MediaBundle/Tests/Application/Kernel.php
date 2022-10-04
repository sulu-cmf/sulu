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

namespace Sulu\Bundle\MediaBundle\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    public function registerBundles(): iterable
    {
        $bundles = parent::registerBundles();

        if (SuluTestKernel::CONTEXT_WEBSITE === $this->getContext()) {
            $bundles[] = new SecurityBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        if (SuluKernel::CONTEXT_WEBSITE === $this->getContext()) {
            $loader->load(__DIR__ . '/config/config_website.yml');
        } else {
            $loader->load(__DIR__ . '/config/config.yml');
        }
    }
}
