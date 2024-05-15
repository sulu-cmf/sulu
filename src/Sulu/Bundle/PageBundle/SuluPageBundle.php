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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class SuluPageBundle extends Bundle
{
    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ContentExportCompilerPass());
        $container->addCompilerPass(new SmartContentDataProviderCompilerPass());
        $container->addCompilerPass(new WebspacesPass());
        $container->addCompilerPass(new StructureExtensionCompilerPass());
        $container->addCompilerPass(new VersioningCompilerPass());
    }
}
