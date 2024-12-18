<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Symfony\Component\Mime\MimeTypes;

/**
 * FileVersion.
 *
 * @phpstan-import-type StorageOptions from StorageInterface
 */
class FileVersion implements AuditableInterface
{
    use AuditableTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $subVersion = 0;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string|null
     */
    private $mimeType;

    /**
     * @var string|null
     */
    private $storageOptions;

    /**
     * @var int
     */
    private $downloadCounter = 0;

    /**
     * @var int
     */
    private $id;

    /**
     * @var DoctrineCollection<int, FileVersionContentLanguage>
     */
    private $contentLanguages;

    /**
     * @var DoctrineCollection<int, FileVersionPublishLanguage>
     */
    private $publishLanguages;

    /**
     * @var DoctrineCollection<int, FileVersionMeta>
     */
    private $meta;

    /**
     * @var DoctrineCollection<string, FormatOptions>
     */
    private $formatOptions;

    /**
     * @var File
     */
    #[Exclude]
    private $file;

    /**
     * @var DoctrineCollection<int, TagInterface>
     */
    private $tags;

    /**
     * @var FileVersionMeta
     */
    private $defaultMeta;

    /**
     * @var string|null
     */
    private $properties = '{}';

    /**
     * @var DoctrineCollection<int, CategoryInterface>
     */
    private $categories;

    /**
     * @var DoctrineCollection<int, TargetGroupInterface>
     */
    private $targetGroups;

    /**
     * @var int|null
     */
    private $focusPointX;

    /**
     * @var int|null
     */
    private $focusPointY;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contentLanguages = new ArrayCollection();
        $this->publishLanguages = new ArrayCollection();
        $this->meta = new ArrayCollection();
        $this->formatOptions = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->targetGroups = new ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return FileVersion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return FileVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Increases the subversion. Required for cache busting on certain operations which change the image without
     * creating a new file version.
     *
     * @return FileVersion
     */
    public function increaseSubVersion()
    {
        ++$this->subVersion;

        return $this;
    }

    /**
     * Get subVersion.
     *
     * @return int
     */
    public function getSubVersion()
    {
        return $this->subVersion;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return FileVersion
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set mimeType.
     *
     * @param string $mimeType
     *
     * @return FileVersion
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType.
     *
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get extension.
     *
     * @return null|string
     */
    public function getExtension()
    {
        $pathInfo = \pathinfo($this->getName());
        $extension = MimeTypes::getDefault()->getExtensions($this->getMimeType() ?? '')[0] ?? null;
        if ($extension) {
            return $extension;
        } elseif (isset($pathInfo['extension'])) {
            return $pathInfo['extension'];
        }

        return null;
    }

    /**
     * @param StorageOptions $storageOptions
     */
    public function setStorageOptions(array $storageOptions)
    {
        $serializedText = \json_encode($storageOptions);
        if (false === $serializedText) {
            return;
        }

        $this->storageOptions = $serializedText;

        return $this;
    }

    /**
     * @return StorageOptions
     */
    public function getStorageOptions(): array
    {
        /** @var StorageOptions|null $storageOptions */
        $storageOptions = \json_decode($this->storageOptions ?? '', true);
        if (!$storageOptions) {
            return [];
        }

        return $storageOptions;
    }

    /**
     * Set downloadCounter.
     *
     * @param int $downloadCounter
     *
     * @return FileVersion
     */
    public function setDownloadCounter($downloadCounter)
    {
        $this->downloadCounter = $downloadCounter;

        return $this;
    }

    /**
     * Get downloadCounter.
     *
     * @return int
     */
    public function getDownloadCounter()
    {
        return $this->downloadCounter;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add contentLanguages.
     *
     * @return FileVersion
     */
    public function addContentLanguage(FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages[] = $contentLanguages;

        return $this;
    }

    /**
     * Remove contentLanguages.
     */
    public function removeContentLanguage(FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages->removeElement($contentLanguages);
    }

    /**
     * Get contentLanguages.
     *
     * @return DoctrineCollection<int, FileVersionContentLanguage>
     */
    public function getContentLanguages()
    {
        return $this->contentLanguages;
    }

    /**
     * Add publishLanguages.
     *
     * @return FileVersion
     */
    public function addPublishLanguage(FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages[] = $publishLanguages;

        return $this;
    }

    /**
     * Remove publishLanguages.
     */
    public function removePublishLanguage(FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages->removeElement($publishLanguages);
    }

    /**
     * Get publishLanguages.
     *
     * @return DoctrineCollection<int, FileVersionPublishLanguage>
     */
    public function getPublishLanguages()
    {
        return $this->publishLanguages;
    }

    /**
     * Add meta.
     *
     * @return FileVersion
     */
    public function addMeta(FileVersionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     */
    public function removeMeta(FileVersionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return DoctrineCollection<int, FileVersionMeta>
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Adds a format-options entity to the file-version.
     *
     * @return FileVersion
     */
    public function addFormatOptions(FormatOptions $formatOptions)
    {
        $this->formatOptions[$formatOptions->getFormatKey()] = $formatOptions;

        return $this;
    }

    /**
     * Get formatOptions.
     *
     * @return DoctrineCollection<string, FormatOptions>
     */
    public function getFormatOptions()
    {
        return $this->formatOptions;
    }

    /**
     * Set file.
     *
     * @return FileVersion
     */
    public function setFile(?File $file = null)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file.
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Add tags.
     *
     * @return FileVersion
     */
    public function addTag(TagInterface $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags.
     */
    public function removeTag(TagInterface $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Remove all tags.
     */
    public function removeTags()
    {
        $this->tags->clear();
    }

    /**
     * Get tags.
     *
     * @return DoctrineCollection<int, TagInterface>
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set defaultMeta.
     *
     * @return FileVersion
     */
    public function setDefaultMeta(?FileVersionMeta $defaultMeta = null)
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    /**
     * Get defaultMeta.
     *
     * @return FileVersionMeta
     */
    public function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    /**
     * don't clone id to create a new entities.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            /** @var FileVersionMeta[] $newMetaList */
            $newMetaList = [];
            $defaultMetaLocale = $this->getDefaultMeta()->getLocale();
            /** @var FileVersionContentLanguage[] $newContentLanguageList */
            $newContentLanguageList = [];
            /** @var FileVersionPublishLanguage[] $newPublishLanguageList */
            $newPublishLanguageList = [];
            /** @var FormatOptions[] $newFormatOptionsArray */
            $newFormatOptionsArray = [];

            foreach ($this->meta as $meta) {
                /* @var FileVersionMeta $meta */
                $newMetaList[] = clone $meta;
            }

            $this->meta->clear();
            foreach ($newMetaList as $newMeta) {
                $newMeta->setFileVersion($this);
                $this->addMeta($newMeta);

                if ($newMeta->getLocale() === $defaultMetaLocale) {
                    $this->setDefaultMeta($newMeta);
                }
            }

            foreach ($this->contentLanguages as $contentLanguage) {
                /* @var FileVersionContentLanguage $contentLanguage */
                $newContentLanguageList[] = clone $contentLanguage;
            }

            $this->contentLanguages->clear();
            foreach ($newContentLanguageList as $newContentLanguage) {
                $newContentLanguage->setFileVersion($this);
                $this->addContentLanguage($newContentLanguage);
            }

            foreach ($this->publishLanguages as $publishLanguage) {
                /* @var FileVersionPublishLanguage $publishLanguage */
                $newPublishLanguageList[] = clone $publishLanguage;
            }

            $this->publishLanguages->clear();
            foreach ($newPublishLanguageList as $newPublishLanguage) {
                $newPublishLanguage->setFileVersion($this);
                $this->addPublishLanguage($newPublishLanguage);
            }

            foreach ($this->formatOptions as $formatOptions) {
                /* @var FormatOptions $formatOptions */
                $newFormatOptionsArray[] = clone $formatOptions;
            }

            $this->formatOptions->clear();
            foreach ($newFormatOptionsArray as $newFormatOptions) {
                /* @var FormatOptions $newFormatOptions */
                $newFormatOptions->setFileVersion($this);
                $this->addFormatOptions($newFormatOptions);
            }
        }
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->version === $this->file->getVersion();
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return \json_decode($this->properties ?? '', true);
    }

    /**
     * @return self
     */
    public function setProperties(array $properties)
    {
        $serializedText = \json_encode($properties);
        if (false === $serializedText) {
            return $this;
        }
        $this->properties = $serializedText;

        return $this;
    }

    /**
     * Add categories.
     *
     * @return self
     */
    public function addCategory(CategoryInterface $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories.
     */
    public function removeCategories()
    {
        $this->categories->clear();
    }

    /**
     * Get categories.
     *
     * @return DoctrineCollection<int, CategoryInterface>
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add a target group.
     */
    public function addTargetGroup(TargetGroupInterface $targetGroup)
    {
        $this->targetGroups[] = $targetGroup;
    }

    /**
     * Remove all target groups.
     */
    public function removeTargetGroups()
    {
        if ($this->targetGroups) {
            $this->targetGroups->clear();
        }
    }

    /**
     * @return DoctrineCollection<int, TargetGroupInterface>
     */
    public function getTargetGroups()
    {
        return $this->targetGroups;
    }

    /**
     * Returns the x coordinate of the focus point.
     *
     * @return int|null
     */
    public function getFocusPointX()
    {
        return $this->focusPointX;
    }

    /**
     * Sets the x coordinate of the focus point.
     *
     * @param int $focusPointX
     */
    public function setFocusPointX($focusPointX)
    {
        $this->focusPointX = $focusPointX;
    }

    /**
     * Returns the y coordinate of the focus point.
     *
     * @return int|null
     */
    public function getFocusPointY()
    {
        return $this->focusPointY;
    }

    /**
     * Sets the y coordinate of the focus point.
     *
     * @param int $focusPointY
     */
    public function setFocusPointY($focusPointY)
    {
        $this->focusPointY = $focusPointY;
    }
}
