<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class ActivateResolveTargetEntityResolverPass implements CompilerPassInterface
{
    /**
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        // TODO we should replace the SuluPersistenceBundle with configuring doctrine.orm.resolve_target_entities in the config
        //      else we need to keep the resolve target entity resolver uptodate ourself.

        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            throw new \RuntimeException('Cannot find Doctrine Target Entity Resolver Listener.');
        }

        $resolveTargetEntityListener = $container->findDefinition('doctrine.orm.listeners.resolve_target_entity');

        // we need to make sure that the service is added as event listener when doctrine bundle is not doing it
        // when the bundle don't get any config
        if (!$resolveTargetEntityListener->hasTag('doctrine.event_listener') // >= doctrine-bundle 2.10.0
            && !$resolveTargetEntityListener->hasTag('doctrine.event_subscriber') // < doctrine-bundle 2.10.0
        ) {
            // we need to configure these events:
            //      https://github.com/doctrine/DoctrineBundle/blob/2.10.0/DependencyInjection/DoctrineExtension.php#L598-L600
            // even when doctrine-bundle < 2.10.0 is used we can use still event_listener as they are supported by our min version of symfony/doctrine-bridge 5.4
            //      https://github.com/symfony/doctrine-bridge/blob/v5.4.0/DependencyInjection/CompilerPass/RegisterEventListenersAndSubscribersPass.php#L72
            $resolveTargetEntityListener
                ->addTag('doctrine.event_listener', ['event' => Events::loadClassMetadata])
                ->addTag('doctrine.event_listener', ['event' => Events::onClassMetadataNotFound]);
        }
    }
}
