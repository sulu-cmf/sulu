<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface as CategoryEntity;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\FileNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

/**
 * Class Media
 * The Media RestObject is the api entity for the MediaController.
 */
#[ExclusionPolicy('all')]
class Media extends ApiWrapper
{
    /**
     * @var string
     */
    public const MEDIA_TYPE_IMAGE = 'image';

    /**
     * @var string
     */
    public const MEDIA_TYPE_VIDEO = 'video';

    /**
     * @var string
     */
    public const MEDIA_TYPE_AUDIO = 'audio';

    /**
     * @var string
     */
    public const MEDIA_TYPE_DOCUMENT = 'document';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $adminUrl;

    /**
     * @var array
     */
    protected $formats = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var array
     */
    protected $additionalVersionData = [];

    /**
     * @var FileVersion
     */
    protected $fileVersion = null;

    /**
     * @var FileVersionMeta
     */
    protected $localizedMeta = null;

    /**
     * @var File
     */
    protected $file = null;

    public function __construct(MediaInterface $media, $locale, $version = null)
    {
        $this->entity = $media;
        $this->locale = $locale;
        $this->version = $version;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['partialMedia', 'Default'])]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('locale')]
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    public function getFallbackLocale()
    {
        if (!$this->getLocalizedMeta()) {
            return;
        }

        $fallbackLocale = $this->getLocalizedMeta()->getLocale();

        return $fallbackLocale !== $this->locale ? $fallbackLocale : null;
    }

    /**
     * @param Collection $collection
     *
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->entity->setCollection($collection);

        return $this;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('collection')]
    public function getCollection()
    {
        $collection = $this->entity->getCollection();
        if ($collection) {
            return $collection->getId();
        }

        return;
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        $this->getFileVersion()->setSize($size);

        return $this;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('size')]
    public function getSize()
    {
        return $this->getFileVersion()->getSize();
    }

    /**
     * @param string $mimeType
     *
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->getFileVersion()->setMimeType($mimeType);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('mimeType')]
    public function getMimeType()
    {
        return $this->getFileVersion()->getMimeType();
    }

    /**
     * @return string|null
     */
    public function getExtension()
    {
        return $this->getFileVersion()->getExtension();
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->getMeta(true)->setTitle($title);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('title')]
    public function getTitle()
    {
        if (!$this->getLocalizedMeta()) {
            return null;
        }

        return $this->getLocalizedMeta()->getTitle();
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->getMeta(true)->setDescription($description);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('description')]
    public function getDescription()
    {
        if (!$this->getLocalizedMeta()) {
            return null;
        }

        return $this->getLocalizedMeta()->getDescription();
    }

    /**
     * @param string|null $copyright
     *
     * @return $this
     */
    public function setCopyright($copyright)
    {
        $this->getMeta(true)->setCopyright($copyright);

        return $this;
    }

    /**
     * Returns copyright for media.
     *
     * @return string|null
     *
     * @throws FileVersionNotFoundException
     */
    #[VirtualProperty]
    #[SerializedName('copyright')]
    public function getCopyright()
    {
        if (!$this->getLocalizedMeta()) {
            return null;
        }

        return $this->getLocalizedMeta()->getCopyright();
    }

    /**
     * @param string|null $credits
     *
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->getMeta(true)->setCredits($credits);

        return $this;
    }

    /**
     * Returns copyright for media.
     *
     * @return string|null
     *
     * @throws FileVersionNotFoundException
     */
    #[VirtualProperty]
    #[SerializedName('credits')]
    public function getCredits()
    {
        if (!$this->getLocalizedMeta()) {
            return null;
        }

        return $this->getLocalizedMeta()->getCredits();
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('version')]
    public function getVersion()
    {
        return $this->getFileVersion()->getVersion();
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('subVersion')]
    public function getSubVersion()
    {
        return $this->getFileVersion()->getSubVersion();
    }

    /**
     * @return array
     */
    public function getAdditionalVersionData()
    {
        return $this->additionalVersionData;
    }

    /**
     * @param array $additionalVersionData
     *
     * @return $this
     */
    public function setAdditionalVersionData($additionalVersionData)
    {
        $this->additionalVersionData = $additionalVersionData;

        return $this;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('versions')]
    public function getVersions()
    {
        $versions = [];

        $file = $this->getFile();

        /** @var FileVersion $fileVersion */
        foreach ($file->getFileVersions() as $fileVersion) {
            $versionData = [];
            if (isset($this->additionalVersionData[$fileVersion->getVersion()])) {
                $versionData = $this->additionalVersionData[$fileVersion->getVersion()];
            }

            $versionData['version'] = $fileVersion->getVersion();
            $versionData['name'] = $fileVersion->getName();
            $versionData['created'] = $fileVersion->getCreated();
            $versionData['changed'] = $fileVersion->getChanged();
            $versionData['active'] = $fileVersion->isActive();
            $versions[$fileVersion->getVersion()] = $versionData;
        }

        return $versions;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->getFileVersion()->setName($name);

        return $this;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('name')]
    public function getName()
    {
        return $this->getFileVersion()->getName();
    }

    /**
     * @return MediaType
     */
    #[VirtualProperty]
    #[SerializedName('type')]
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * @param MediaType $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isTypeOf($type)
    {
        return $this->getType()->getName() == $type;
    }

    /**
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('isImage')]
    public function isImage()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_IMAGE);
    }

    /**
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('isVideo')]
    public function isVideo()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_VIDEO);
    }

    /**
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('isAudio')]
    public function isAudio()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_AUDIO);
    }

    /**
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('isDocument')]
    public function isDocument()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_DOCUMENT);
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('storageOptions')]
    public function getStorageOptions()
    {
        return $this->getFileVersion()->getStorageOptions();
    }

    /**
     * @param array<string, string|null> $storageOptions
     *
     * @return $this
     */
    public function setStorageOptions($storageOptions)
    {
        $this->getFileVersion()->setStorageOptions($storageOptions);

        return $this;
    }

    /**
     * @param array $publishLanguages
     *
     * @return $this
     */
    public function setPublishLanguages($publishLanguages)
    {
        $fileVersion = $this->getFileVersion();

        foreach ($publishLanguages as $locale) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setFileVersion($fileVersion);
            $publishLanguage->setLocale($locale);
            if (!$fileVersion->getPublishLanguages()->contains($publishLanguage)) {
                $fileVersion->addPublishLanguage($publishLanguage);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('publishLanguages')]
    public function getPublishLanguages()
    {
        $publishLanguages = [];
        /** @var FileVersionPublishLanguage $publishLanguage */
        foreach ($this->getFileVersion()->getPublishLanguages() as $publishLanguage) {
            $publishLanguages[] = $publishLanguage->getLocale();
        }

        return $publishLanguages;
    }

    /**
     * @param array $contentLanguages
     *
     * @return $this
     */
    public function setContentLanguages($contentLanguages)
    {
        $fileVersion = $this->getFileVersion();

        foreach ($contentLanguages as $locale) {
            $contentLanguage = new FileVersionContentLanguage();
            $contentLanguage->setFileVersion($fileVersion);
            $contentLanguage->setLocale($locale);
            if (!$fileVersion->getContentLanguages()->contains($contentLanguage)) {
                $fileVersion->addContentLanguage($contentLanguage);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('contentLanguages')]
    public function getContentLanguages()
    {
        $contentLanguages = [];
        /** @var FileVersionContentLanguage $contentLanguage */
        foreach ($this->getFileVersion()->getContentLanguages() as $contentLanguage) {
            $contentLanguages[] = $contentLanguage->getLocale();
        }

        return $contentLanguages;
    }

    /**
     * @return $this
     */
    public function removeTags()
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTags();

        return $this;
    }

    /**
     * @return $this
     */
    public function addTag(TagInterface $tagEntity)
    {
        $fileVersion = $this->getFileVersion();
        if (!$fileVersion->getTags()->contains($tagEntity)) {
            $fileVersion->addTag($tagEntity);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    #[VirtualProperty]
    #[SerializedName('tags')]
    public function getTags()
    {
        $tags = [];
        foreach ($this->getFileVersion()->getTags() as $tag) {
            /* @var TagInterface $tag */
            \array_push($tags, $tag->getName());
        }

        return $tags;
    }

    /**
     * @return array
     */
    #[SerializedName('thumbnails')]
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('thumbnails')]
    public function getThumbnails() // FIXME change to getPreviews when SerializedName working
    {
        return $this->formats;
    }

    /**
     * @param array $formats
     */
    public function setFormats($formats)
    {
        $this->formats = $formats;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('url')]
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('adminUrl')]
    public function getAdminUrl()
    {
        // if the admin url is not set fallback to the website url for backward compatibility
        if (!$this->adminUrl) {
            return $this->url;
        }

        return $this->adminUrl;
    }

    /**
     * @param string $adminUrl
     */
    public function setAdminUrl($adminUrl)
    {
        $this->adminUrl = $adminUrl;
    }

    /**
     * @param \DateTime|string $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        if (\is_string($changed)) {
            $changed = new \DateTime($changed);
        }
        $this->getFileVersion()->setChanged($changed);

        return $this;
    }

    /**
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('changed')]
    public function getChanged()
    {
        return $this->getFileVersion()->getChanged();
    }

    /**
     * @param UserInterface|null $changer
     *
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->entity->setChanger($changer);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('changer')]
    public function getChanger()
    {
        $user = $this->getFileVersion()->getChanger();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('created')]
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * @param UserInterface|null $creator
     *
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * @return string|null
     */
    #[VirtualProperty]
    #[SerializedName('creator')]
    public function getCreator()
    {
        $user = $this->getFileVersion()->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->getFileVersion()->setProperties($properties);

        return $this;
    }

    /**
     * @return array|null
     */
    #[VirtualProperty]
    #[SerializedName('properties')]
    public function getProperties()
    {
        $properties = $this->getFileVersion()->getProperties();
        if (null === $properties) {
            return null;
        }
        Assert::isArray($properties);

        return $properties;
    }

    /**
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('downloadCounter')]
    public function getDownloadCounter()
    {
        $downloadCounter = 0;
        foreach ($this->getEntity()->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $downloadCounter += \intval($fileVersion->getDownloadCounter());
            }
        }

        return $downloadCounter;
    }

    /**
     * @return FileVersion
     *
     * @throws FileVersionNotFoundException
     */
    public function getFileVersion()
    {
        if (null !== $this->fileVersion) {
            return $this->fileVersion;
        }

        /** @var File $file */
        foreach ($this->entity->getFiles() as $file) {
            if (null !== $this->version) {
                $version = $this->version;
            } else {
                $version = $file->getVersion();
            }

            if ($fileVersion = $file->getFileVersion($version)) {
                $this->fileVersion = $fileVersion;

                return $fileVersion;
            }
            break; // currently only one file per media exists
        }

        throw new FileVersionNotFoundException($this->entity->getId(), $this->version);
    }

    /**
     * @return File
     *
     * @throws FileNotFoundException
     */
    public function getFile()
    {
        if (null !== $this->file) {
            return $this->file;
        }

        /** @var File $file */
        foreach ($this->entity->getFiles() as $file) {
            // currently only one file per media exists
            $this->file = $file;

            return $this->file;
        }

        throw new FileNotFoundException($this->entity->getId());
    }

    /**
     * @param bool $create
     *
     * @return FileVersionMeta
     */
    private function getMeta($create = false)
    {
        $locale = $this->locale;
        $metaCollection = $this->getFileVersion()->getMeta();

        // get meta only with this locale
        $metaCollectionFiltered = $metaCollection->filter(function($meta) use ($locale) {
            /** @var FileVersionMeta $meta */
            if ($meta->getLocale() == $locale) {
                return true;
            }

            return false;
        });

        // check if meta was found
        if ($metaCollectionFiltered->isEmpty()) {
            if ($create) {
                // create when not found
                $meta = new FileVersionMeta();
                $meta->setLocale($this->locale);
                $meta->setFileVersion($this->getFileVersion());
                $this->getFileVersion()->addMeta($meta);

                return $meta;
            }

            // return first when create false
            return $this->getFileVersion()->getDefaultMeta();
        }

        // return exists
        return $metaCollectionFiltered->first();
    }

    /**
     * Searches the meta for the file version in the media locale. Might also return a fallback.
     *
     * @return FileVersionMeta
     *
     * @throws FileVersionNotFoundException
     */
    private function getLocalizedMeta()
    {
        if ($this->localizedMeta) {
            return $this->localizedMeta;
        }

        $this->localizedMeta = $this->getFileVersion()->getDefaultMeta();

        foreach ($this->getFileVersion()->getMeta() as $meta) {
            if ($meta->getLocale() == $this->locale) {
                $this->localizedMeta = $meta;
                break;
            }
        }

        return $this->localizedMeta;
    }

    /**
     * Adds a category to the entity.
     */
    public function addCategory(CategoryEntity $category)
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->addCategory($category);

        return $this;
    }

    /**
     * Removes all category from the entity.
     *
     * @return self
     */
    public function removeCategories()
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeCategories();

        return $this;
    }

    /**
     * Returns the categories of the media.
     *
     * @return int[]
     */
    #[VirtualProperty]
    #[SerializedName('categories')]
    public function getCategories()
    {
        $apiCategories = [];
        $fileVersion = $this->getFileVersion();
        $categories = $fileVersion->getCategories();

        return \array_map(function(CategoryEntity $category) {
            return $category->getId();
        }, $categories->toArray());
    }

    /**
     * Adds a target group to the entity.
     *
     * @return self
     */
    public function addTargetGroup(TargetGroupInterface $targetGroup)
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->addTargetGroup($targetGroup);

        return $this;
    }

    /**
     * Removes all target groups from the entities.
     *
     * @return self
     */
    public function removeTargetGroups()
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTargetGroups();

        return $this;
    }

    /**
     * Returns the target groups of the media.
     *
     * @return int[]
     */
    #[VirtualProperty]
    #[SerializedName('targetGroups')]
    #[Groups(['fullMediaAudienceTargeting'])]
    public function getTargetGroups()
    {
        if (!$this->getFileVersion()->getTargetGroups()) {
            return [];
        }

        return \array_map(function(TargetGroupInterface $targetGroup) {
            return $targetGroup->getId();
        }, $this->getFileVersion()->getTargetGroups()->toArray());
    }

    /**
     * Returns the x coordinate of the focus point.
     *
     * @return int|null
     */
    #[VirtualProperty]
    #[SerializedName('focusPointX')]
    public function getFocusPointX()
    {
        return $this->getFileVersion()->getFocusPointX();
    }

    /**
     * Sets the x coordinate of the focus point.
     *
     * @param int $focusPointX
     */
    public function setFocusPointX($focusPointX)
    {
        $this->getFileVersion()->setFocusPointX($focusPointX);
    }

    /**
     * Returns the y coordinate of the focus point.
     *
     * @return int|null
     */
    #[VirtualProperty]
    #[SerializedName('focusPointY')]
    public function getFocusPointY()
    {
        return $this->getFileVersion()->getFocusPointY();
    }

    /**
     * Sets the y coordinate of the focus point.
     *
     * @param int $focusPointY
     */
    public function setFocusPointY($focusPointY)
    {
        $this->getFileVersion()->setFocusPointY($focusPointY);
    }

    /**
     * @return ?int
     */
    #[VirtualProperty]
    #[SerializedName('previewImageId')]
    public function getPreviewImageId()
    {
        $previewImage = $this->entity->getPreviewImage();

        if (!$previewImage) {
            return null;
        }

        return $previewImage->getId();
    }
}
