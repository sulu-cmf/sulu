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

/**
* @internal
*/
class AliasForSecurityEncoderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasAlias('security.encoder_factory')) {
            // @deprecated Symfony 5.4 backward compatibility bridge
            $container->setAlias('sulu_security.encoder_factory', 'security.encoder_factory')->setPublic(true);
        } elseif ($container->hasDefinition('security.password_hasher_factory')) {
            $container->setAlias('sulu_security.encoder_factory', 'security.password_hasher_factory')->setPublic(true);
        }
    }
}
