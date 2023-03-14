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
    protected $rootDir;

    /**
     * @var string
     */
    private $projectDir;

    public function registerContainerConfiguration(LoaderInterface $loader)
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
    protected function getContainerClass()
    {
        // use parent class to normalize the generated container class.
        return $this->generateContainerClass(\get_parent_class());
    }

    public function getRootDir(/* $triggerDeprecation = true */)
    {
        if (0 === \func_num_args() || \func_get_arg(0)) {
            @trigger_deprecation('symfony/symfony', '4.2', 'The "%s()" method is deprecated, use getProjectDir() instead.', __METHOD__);
        }

        if (null === $this->rootDir) {
            $reflectionClass = new \ReflectionClass(Kernel::class);
            $this->rootDir = \dirname($reflectionClass->getFileName());
        }

        return $this->rootDir;
    }

    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $reflectionClass = new \ReflectionClass(Kernel::class);
            $dir = $rootDir = \dirname($reflectionClass->getFileName());
            while (!\file_exists($dir . '/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    public function getLogDir()
    {
        $context = $this->getContext();
        $this->setContext(static::CONTEXT_PREVIEW);

        $logDirectory = parent::getLogDir();

        $this->setContext($context);

        return $logDirectory;
    }

    public function getCacheDir()
    {
        $context = $this->getContext();
        $this->setContext(static::CONTEXT_PREVIEW);

        $cacheDirectory = parent::getCacheDir();

        $this->setContext($context);

        return $cacheDirectory;
    }

    public function getKernelParameters()
    {
        return \array_merge(
            parent::getKernelParameters(),
            ['sulu.preview' => true]
        );
    }
}
