<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\WebspaceUrlProviderCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterContentTypesCompilerPass());
        $container->addCompilerPass(new RegisterLocalizationProvidersPass());
        $container->addCompilerPass(new RemoveForeignContextServicesPass());
        $container->addCompilerPass(new ReplacersCompilerPass(__DIR__ . '/DataFixtures/replacers.xml'));
        $container->addCompilerPass(new ListBuilderMetadataProviderCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_core.webspace.request_analyzer', 'sulu.request_attributes', 1)
        );
        $container->addCompilerPass(new CsvHandlerCompilerPass());
        $container->addCompilerPass(new WebspaceUrlProviderCompilerPass());
    }
}
