<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle;

use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\ParserCompilerPass;
use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\TagCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Integrates markup into symfony.
 */
class SuluMarkupBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ParserCompilerPass());
        $container->addCompilerPass(new TagCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_markup.parser.delegating_html_extractor',
                'sulu_markup.parser.html_extractor'
            )
        );
    }
}
