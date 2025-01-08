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

use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\TagCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
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

        $container->addCompilerPass(new TagCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_markup.parser.delegating_html_extractor',
                'sulu_markup.parser.html_extractor'
            )
        );
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_markup.link_tag.provider_pool',
                'sulu.link.provider',
                0,
                'alias'
            )
        );
    }
}
