<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle;

use Sulu\Bundle\ArticleBundle\DependencyInjection\ConverterCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\RouteEnhancerCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\StructureValidatorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry-point for article-bundle.
 */
class SuluArticleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RouteEnhancerCompilerPass());
        $container->addCompilerPass(new ConverterCompilerPass());
        $container->addCompilerPass(new StructureValidatorCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
