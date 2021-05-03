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

use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class UserManagerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = new Definition(
            UserManager::class,
            [
                new Reference('doctrine.orm.entity_manager'),
                $container->hasDefinition('security.token_storage') ? new Reference('security.encoder_factory') : null,
                new Reference('sulu.repository.role'),
                new Reference('sulu_security.group_repository'),
                new Reference('sulu_contact.contact_manager'),
                new Reference('sulu_security.salt_generator'),
                new Reference('sulu.repository.user'),
                new Reference('sulu_event_log.domain_event_collector'),
            ]
        );
        $definition->setPublic(true);

        $container->setDefinition('sulu_security.user_manager', $definition);
    }
}
