<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

use App\Kernel;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection\Compiler\RegisterPreviewWebspaceClassPass;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Extends website-kernel from sulu-installation and override configuration.
 */
class PreviewKernel extends Kernel
{
    public const CONTEXT_PREVIEW = 'preview';

    /**
     * @var string
     */
    private $projectDir;

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(function(ContainerBuilder $container) use ($loader) {
            // disable web_profiler toolbar in preview if the web_profiler extension exist
            if ($container->hasExtension('web_profiler')) {
                $loader->load(
                    \implode(\DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'config', 'config_preview_dev.yml'])
                );
            }
        });

        $loader->load(\implode(\DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Resources', 'config', 'config_preview.yml']));
    }

    /**
     * The "getContainerClass" need to be normalized for preview and other contexts
     * as it is used by the symfony cache component as prefix.
     *
     * @see SuluKernel::getContainerClass
     */
    protected function getContainerClass(): string
    {
        // use parent class to normalize the generated container class.
        return $this->generateContainerClass(parent::class);
    }

    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $r = new \ReflectionClass(Kernel::class); // uses App\Kernel to cache dirs and co. correctly

            /** @var string $dir */
            $dir = $r->getFileName();

            if (!\is_file($dir)) {
                throw new \LogicException(\sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!\is_file($dir . '/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    public function getLogDir(): string
    {
        $context = $this->getContext();
        $this->setContext(static::CONTEXT_PREVIEW);

        $logDirectory = parent::getLogDir();

        $this->setContext($context);

        return $logDirectory;
    }

    public function getCacheDir(): string
    {
        $context = $this->getContext();
        $this->setContext(static::CONTEXT_PREVIEW);

        $cacheDirectory = parent::getCacheDir();

        $this->setContext($context);

        return $cacheDirectory;
    }

    public function getKernelParameters(): array
    {
        return \array_merge(
            parent::getKernelParameters(),
            ['sulu.preview' => true]
        );
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterPreviewWebspaceClassPass());
        parent::build($container);
    }
}
