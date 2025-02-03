<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle;

use Sulu\Bundle\SnippetBundle\DependencyInjection\Compiler\SnippetAreaCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluSnippetBundle extends Bundle
{
    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SnippetAreaCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1024);

        parent::build($container);
    }
}
