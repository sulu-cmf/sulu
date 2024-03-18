<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $envSpecificFile = __DIR__ . '/config/' . $this->getEnvironment() . '/config.yaml';
        $loader->load(__DIR__ . '/config/webspaces/sulu-io.xml');

        if (\file_exists($envSpecificFile)) {
            $loader->load($envSpecificFile);
        }
    }
}
