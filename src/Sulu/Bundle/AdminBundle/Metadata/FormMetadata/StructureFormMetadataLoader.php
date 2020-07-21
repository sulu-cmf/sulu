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

use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata as ContentStructureMetadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class StructureFormMetadataLoader implements FormMetadataLoaderInterface, CacheWarmerInterface
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var FormMetadataMapper
     */
    private $formMetadataMapper;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string[]
     */
    private $defaultTypes;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        FormMetadataMapper $formMetadataMapper,
        WebspaceManagerInterface $webspaceManager,
        array $defaultTypes,
        array $locales,
        string $cacheDir,
        bool $debug
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->formMetadataMapper = $formMetadataMapper;
        $this->webspaceManager = $webspaceManager;
        $this->defaultTypes = $defaultTypes;
        $this->locales = $locales;
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

        if (isset($metadataOptions['webspace'])) {
            $webspace = $this->webspaceManager->findWebspaceByKey($metadataOptions['webspace']);

            $form->setDefaultType($webspace->getDefaultTemplate($key));

            foreach ($webspace->getExcludedTemplates() as $excludedTemplate) {
                $form->removeForm($excludedTemplate);
            }
        } elseif (isset($this->defaultTypes[$key])) {
            $form->setDefaultType($this->defaultTypes[$key]);
        }

        if (isset($metadataOptions['defaultTemplate']) && isset($form->getForms()[$metadataOptions['defaultTemplate']])) {
            $form->setDefaultType($metadataOptions['defaultTemplate']);
        }

        return $form;
    }

    public function warmUp($cacheDir)
    {
        $structuresMetadataByTypes = [];
        foreach ($this->structureMetadataFactory->getStructureTypes() as $structureType) {
            foreach ($this->structureMetadataFactory->getStructures($structureType) as $structureMetadata) {
                if ($structureMetadata->isInternal() || 'home' === $structureType) {
                    continue;
                }

                $structuresMetadataByTypes[$structureType][] = $structureMetadata;
            }
        }

        foreach ($this->locales as $locale) {
            foreach ($structuresMetadataByTypes as $structureType => $structuresMetadata) {
                $structure = $this->mapStructureMetadata($structuresMetadata, $locale);
                $configCache = $this->getConfigCache($structureType, $locale);
                $configCache->write(
                    \serialize($structure),
                    \array_map(function(ContentStructureMetadata $structureMetadata) {
                        return new FileResource($structureMetadata->getResource());
                    }, $structuresMetadata)
                );
            }
        }
    }

    /**
     * @param StructureMetadata[] $structuresMetadata
     */
    private function mapStructureMetadata(array $structuresMetadata, string $locale): TypedFormMetadata
    {
        $typedForm = new TypedFormMetadata();

        foreach ($structuresMetadata as $structureMetadata) {
            $form = new FormMetadata();
            $form->setTags($this->formMetadataMapper->mapTags($structureMetadata->getTags()));
            $form->setName($structureMetadata->getName());
            $form->setTitle($structureMetadata->getTitle($locale) ?? \ucfirst($structureMetadata->getName()));
            $form->setItems($this->formMetadataMapper->mapChildren($structureMetadata->getChildren(), $locale));
            $form->setSchema($this->formMetadataMapper->mapSchema($structureMetadata->getProperties()));

            $this->enhanceBlockMetadata($form->getItems());

            $typedForm->addForm($structureMetadata->getName(), $form);
        }

        return $typedForm;
    }

    private function enhanceBlockMetadata(array $itemsMetadata): void
    {
        foreach ($itemsMetadata as $itemMetadata) {
            if ('block' === $itemMetadata->getType()) {
                $optionMetadata = new OptionMetadata();
                $optionMetadata->setName('settings_form_key');
                $optionMetadata->setValue('page_block_settings');
                $itemMetadata->addOption($optionMetadata);

                foreach ($itemMetadata->getTypes() as $type) {
                    $this->enhanceBlockMetadata($type->getItems());
                }
            }

            if ($itemMetadata instanceof SectionMetadata) {
                $this->enhanceBlockMetadata($itemMetadata->getItems());
            }
        }
    }

    public function isOptional()
    {
        return false;
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(\sprintf('%s%s%s.%s', $this->cacheDir, \DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
