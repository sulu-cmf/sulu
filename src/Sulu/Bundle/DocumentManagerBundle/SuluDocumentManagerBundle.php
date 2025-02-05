<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle;

use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\DocumentFixturePass;
use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\InitializerPass;
use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\RegisterListenersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluDocumentManagerBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new InitializerPass());
        $container->addCompilerPass(new RegisterListenersPass(
            'sulu_document_manager.event_dispatcher',
            'sulu_document_manager.event_listener',
            'sulu_document_manager.event_subscriber'
        ), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new DocumentFixturePass());
    }
}
