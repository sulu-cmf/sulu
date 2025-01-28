<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Sulu\Bundle\MediaBundle\Media\FormatLoader\XmlFormatLoader10;
use Sulu\Bundle\MediaBundle\Media\FormatLoader\XmlFormatLoader11;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * This abstract class contains all the shared logic to load image formats.
 *
 * @internal this is an internal class which should not be used by a project
 */
abstract class AbstractImageFormatCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $globalOptions;

    public function process(ContainerBuilder $container)
    {
        $this->globalOptions = $container->getParameter('sulu_media.format_manager.default_imagine_options');

        $formats = [];

        $files = $this->getFiles($container);
        foreach ($files as $file) {
            if (\file_exists($file)) {
                $this->loadFormatsFromFile($file, $formats);
                $container->addResource(new FileResource($file));
            }
        }

        if ($container->hasParameter('sulu_media.image.formats')) {
            $formats = \array_merge($container->getParameter('sulu_media.image.formats'), $formats);
        }

        $container->setParameter('sulu_media.image.formats', $formats);
    }

    /**
     * Returns the paths to all the image format files which should be loaded by this compiler pass.
     *
     * @return string[]
     */
    abstract protected function getFiles(ContainerBuilder $container);

    /**
     * Adds the image formats from the file at the given path to the given array.
     *
     * @param string $path
     */
    private function loadFormatsFromFile($path, array &$formats)
    {
        $folder = \dirname($path);
        $file = \basename($path);

        $locator = new FileLocator($folder);

        $xmlLoader10 = new XmlFormatLoader10($locator);
        $xmlLoader11 = new XmlFormatLoader11($locator);
        $xmlLoader10->setGlobalOptions($this->globalOptions);
        $xmlLoader11->setGlobalOptions($this->globalOptions);

        $resolver = new LoaderResolver([$xmlLoader10, $xmlLoader11]);
        $loader = new DelegatingLoader($resolver);

        $fileFormats = $loader->load($file);
        foreach ($fileFormats as $format) {
            if (\array_key_exists($format['key'], $formats) && $formats[$format['key']] !== $format) {
                throw new InvalidArgumentException(
                    \sprintf('Media format with key "%s" already exists!', $format['key'])
                );
            }

            $formats[$format['key']] = $format;
        }
    }
}
