<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle;

use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\ParserCompilerPass;
use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\TagCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Integrates markup into symfony.
 *
 * @final
 */
class SuluMarkupBundle extends Bundle
{
    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ParserCompilerPass());
        $container->addCompilerPass(new TagCompilerPass());
    }
}
