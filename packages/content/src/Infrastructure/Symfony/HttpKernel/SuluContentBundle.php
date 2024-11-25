<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Symfony\HttpKernel;

use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\SettingsFormPass;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluContentBundle extends AbstractBundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode(); // @phpstan-ignore-line
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // TODO refactor to PHP based service definitions
        $loader = new XmlFileLoader($builder, new FileLocator(\dirname(__DIR__, 4) . '/config'));
        $loader->load('data-mapper.xml');
        $loader->load('merger.xml');
        $loader->load('normalizer.xml');
        $loader->load('services.xml');
        $loader->load('form-visitor.xml');
        $loader->load('controller.xml');
        $loader->load('resolvers.xml');
        $loader->load('resource-loader.xml');

        if ($builder->hasParameter('kernel.bundles')) {
            // TODO FIXME add test here
            // @codeCoverageIgnoreStart
            /** @var string[] $bundles */
            $bundles = $builder->getParameter('kernel.bundles');

            if (\array_key_exists('SuluAutomationBundle', $bundles)) {
                $loader->load('automation.xml');
            }
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('sulu_admin')) {
            $builder->prependExtensionConfig(
                'sulu_admin',
                [
                    'forms' => [
                        'directories' => [
                            \dirname(__DIR__, 4) . '/config/forms',
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SettingsFormPass());
    }
}
