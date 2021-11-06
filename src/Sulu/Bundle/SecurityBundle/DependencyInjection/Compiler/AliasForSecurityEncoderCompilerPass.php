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

class AliasForSecurityEncoderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasAlias('security.password_hasher_factory')) {
            // the service "security.encoder_factory" is private use an alias to make it public
            $container->setAlias('sulu_security.encoder_factory', 'security.password_hasher_factory')->setPublic(true);
        }
    }
}
