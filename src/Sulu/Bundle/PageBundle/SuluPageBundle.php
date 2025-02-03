<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use Sulu\Bundle\PageBundle\DependencyInjection\Compiler\ContentExportCompilerPass;
use Sulu\Bundle\PageBundle\DependencyInjection\Compiler\SmartContentDataProviderCompilerPass;
use Sulu\Bundle\PageBundle\DependencyInjection\Compiler\StructureExtensionCompilerPass;
use Sulu\Bundle\PageBundle\DependencyInjection\Compiler\VersioningCompilerPass;
use Sulu\Bundle\PageBundle\DependencyInjection\Compiler\WebspacesPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluPageBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ContentExportCompilerPass());
        $container->addCompilerPass(new SmartContentDataProviderCompilerPass());
        $container->addCompilerPass(new WebspacesPass());
        $container->addCompilerPass(new StructureExtensionCompilerPass());
        $container->addCompilerPass(new VersioningCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_page.teaser.provider_pool',
                'sulu.teaser.provider',
                0,
                'alias'
            )
        );
    }
}
