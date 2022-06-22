<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwoFactorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $methods = [];

        if ($container->has('scheb_two_factor.security.email.code_generator')) {
            $methods[] = 'email';
        }

        if ($container->hasParameter('scheb_two_factor.trusted_device.enabled')
            && $container->getParameter('scheb_two_factor.trusted_device.enabled')
        ) {
            $methods[] = 'trusted_devices';
        }

        $container->setParameter('sulu_security.two_factor_methods', $methods);
    }
}
