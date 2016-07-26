<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiles replacers.xml into a container parameter.
 */
class ReplacersCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * ReplacersCompilerPass constructor.
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $this->loader = $container->get('sulu.content.path_cleaner.replacer_loader');

        $service = $container->getDefinition('sulu.content.path_cleaner');
        $replacers = $this->loader->load($this->filename);
        $service->addArgument($replacers);
    }
}
