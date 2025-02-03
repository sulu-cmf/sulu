<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle;

use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\CsvHandlerCompilerPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\ListBuilderMetadataProviderCompilerPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterContentTypesCompilerPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterLocalizationProvidersPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\ReplacersCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluCoreBundle extends Bundle
{
    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterContentTypesCompilerPass());
        $container->addCompilerPass(new RegisterLocalizationProvidersPass());
        $container->addCompilerPass(new RemoveForeignContextServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 99);
        $container->addCompilerPass(new ReplacersCompilerPass(__DIR__ . '/DataFixtures/replacers.xml'));
        $container->addCompilerPass(new ListBuilderMetadataProviderCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_core.webspace.request_analyzer', 'sulu.request_attributes', 1)
        );
        $container->addCompilerPass(new CsvHandlerCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_core.webspace.url_provider', 'sulu.webspace.url_provider')
        );
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu.content.resource_locator.strategy_pool',
                'sulu.resource_locator.strategy',
                0,
                'alias'
            )
        );
    }
}
