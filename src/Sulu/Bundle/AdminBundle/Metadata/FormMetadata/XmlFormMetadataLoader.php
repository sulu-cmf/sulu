<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class XmlFormMetadataLoader implements FormMetadataLoaderInterface, CacheWarmerInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var FieldMetadataValidatorInterface
     */
    private $fieldMetadataValidator;

    /**
     * @var string[]
     */
    private $formDirectories;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        FieldMetadataValidatorInterface $fieldMetadataValidator,
        array $formDirectories,
        string $cacheDir,
        bool $debug
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->fieldMetadataValidator = $fieldMetadataValidator;
        $this->formDirectories = $formDirectories;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): ?MetadataInterface
    {
        $configCache = $this->getConfigCache($key, $locale);

        if (!\file_exists($configCache->getPath())) {
            return null;
        }

        if (!$configCache->isFresh()) {
            $this->warmUp($this->cacheDir);
        }

        $form = \unserialize(\file_get_contents($configCache->getPath()));

        return $form;
    }

    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        $formsMetadataCollection = [];
        $formsMetadataResources = [];

        foreach ($formFinder as $formFile) {
            $formMetadataCollection = $this->formXmlLoader->load($formFile->getPathName());
            $items = $formMetadataCollection->getItems();
            $formKey = \reset($items)->getKey();
            $formsMetadataResources[$formKey][] = $formFile->getPathName();
            if (!\array_key_exists($formKey, $formsMetadataCollection)) {
                $formsMetadataCollection[$formKey] = $formMetadataCollection;
            } else {
                $formsMetadataCollection[$formKey] = $formsMetadataCollection[$formKey]->merge($formMetadataCollection);
            }
        }

        foreach ($formsMetadataCollection as $key => $formMetadataCollection) {
            foreach ($formMetadataCollection->getItems() as $locale => $formMetadata) {
                $this->validateItems($formMetadata->getItems(), $key);

                $configCache = $this->getConfigCache($key, $locale);
                $configCache->write(
                    \serialize($formMetadata),
                    \array_map(function(string $resource) {
                        return new FileResource($resource);
                    }, $formsMetadataResources[$key])
                );
            }
        }

        return [];
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function validateItems(array $items, string $formKey): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->validateItems($item->getItems(), $formKey);
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->validateItems($type->getItems(), $formKey);
                }

                $this->fieldMetadataValidator->validate($item, $formKey);
            }
        }
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(\sprintf('%s%s%s.%s', $this->cacheDir, \DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
