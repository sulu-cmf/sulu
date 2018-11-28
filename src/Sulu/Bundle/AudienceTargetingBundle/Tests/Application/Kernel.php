<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Kernel extends SuluTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        if ('admin' === $this->getContext()) {
            $loader->load(__DIR__ . '/config/config_admin.yml');
        } else {
            $loader->load(__DIR__ . '/config/config_website.yml');
        }
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // emulate that the target group had an influence on the result
        $this->getContainer()->get('sulu_audience_targeting.target_group_store')->getTargetGroupId();

        return parent::handle($request, $type, $catch);
    }
}
