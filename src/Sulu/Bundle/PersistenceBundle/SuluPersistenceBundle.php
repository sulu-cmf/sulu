<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ActivateResolveTargetEntityResolverPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluPersistenceBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new ActivateResolveTargetEntityResolverPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            10 // need to be run before the "EntityListenerPass" of the "DoctrineBundle"
        );
    }
}
