<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use FFMpeg\FFProbe;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMediaTypeException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\MediaPropertiesProvider;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\MediaPropertiesProviderInterface;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\VideoPropertiesProvider;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Default implementation of media manager.
 */
class MediaManager implements MediaManagerInterface
{
    const ENTITY_NAME_COLLECTION = 'SuluMediaBundle:Collection';

    /**
     * The repository for communication with the database.
     *
     * @var MediaRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * The repository for communication with the database.
     *
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var TargetGroupRepositoryInterface
     */
    protected $targetGroupRepository;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FileValidatorInterface
     */
    private $validator;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var TypeManagerInterface
     */
    protected $typeManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var PathCleanupInterface
     */
    private $pathCleaner;

    /**
     * @var string
     */
    private $downloadPath;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var int
     */
    public $count;

    /**
     * @var string
     */
    private $adminDownloadPath;

    /**
     * @var MediaPropertiesProviderInterface[]
     */
    private $propertiesProvider;

    /**
     * @param null|FFprobe|MediaPropertiesProviderInterface $propertiesProvider
     * @param array $permissions
     * @param string $downloadPath
     * @param string $maxFileSize
     * @param string $adminDownloadPath
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        CollectionRepositoryInterface $collectionRepository,
        UserRepositoryInterface $userRepository,
        CategoryRepositoryInterface $categoryRepository,
        EntityManager $em,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        FormatManagerInterface $formatManager,
        TagManagerInterface $tagManager,
        TypeManagerInterface $typeManager,
        PathCleanupInterface $pathCleaner,
        TokenStorageInterface $tokenStorage = null,
        SecurityCheckerInterface $securityChecker = null,
        $propertiesProvider = null,
        $permissions,
        $downloadPath,
        $maxFileSize,
        TargetGroupRepositoryInterface $targetGroupRepository = null,
        $adminDownloadPath = null
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->em = $em;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->formatManager = $formatManager;
        $this->tagManager = $tagManager;
        $this->typeManager = $typeManager;
        $this->pathCleaner = $pathCleaner;
        $this->tokenStorage = $tokenStorage;
        $this->securityChecker = $securityChecker;
        $this->permissions = $permissions;
        $this->downloadPath = $downloadPath;
        $this->maxFileSize = $maxFileSize;

        if (!$adminDownloadPath) {
            @\trigger_error(
                \sprintf(
                    'The usage of the "%s" without setting the "$adminDownloadPath" is deprecated and will not longer work in Sulu 3.0.',
                    MediaManager::class
                ),
                \E_USER_DEPRECATED
            );
        }

        $this->adminDownloadPath = $adminDownloadPath ?: '/admin' . $this->downloadPath;

        if (!$propertiesProvider instanceof MediaPropertiesProviderInterface) {
            @\trigger_error(
                \sprintf(
                    'The usage of the "%s" without setting "$propertiesProvider" is deprecated and will not longer work in Sulu 3.0.',
                    MediaManager::class
                ),
                \E_USER_DEPRECATED
            );

            if ($propertiesProvider instanceof FFProbe) {
                $propertiesProvider = new MediaPropertiesProvider([
                    new VideoPropertiesProvider($propertiesProvider),
                ]);
            } else {
                $propertiesProvider = new MediaPropertiesProvider([]);
            }
        }

        $this->propertiesProvider = $propertiesProvider;
    }

    public function getById($id, $locale)
    {
        $mediaEntity = $this->getEntityById($id);

        return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
    }

    public function getEntityById($id)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);
        if (!$mediaEntity) {
            throw new MediaNotFoundException($id);
        }

        return $mediaEntity;
    }

    public function getByIds(array $ids, $locale, $permission = null)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia(
            ['pagination' => false, 'ids' => $ids],
            null,
            null,
            $this->getCurrentUser(),
            $permission
        );
        $this->count = \count($mediaEntities);
        foreach ($mediaEntities as $mediaEntity) {
            $media[\array_search($mediaEntity->getId(), $ids)] = $this->addFormatsAndUrl(
                new Media($mediaEntity, $locale, null)
            );
        }

        \ksort($media);

        return \array_values($media);
    }

    public function get($locale, $filter = [], $limit = null, $offset = null, $permission = null)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia(
            $filter,
            $limit,
            $offset,
            $this->getCurrentUser(),
            $permission
        );
        $this->count = $this->mediaRepository->count($filter);

        foreach ($mediaEntities as $mediaEntity) {
            $media[] = $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        }

        return $media;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function save($uploadedFile, $data, $userId)
    {
        if (isset($data['id'])) {
            $media = $this->modifyMedia($uploadedFile, $data, $this->getUser($userId));
        } else {
            $media = $this->buildData($uploadedFile, $data, $this->getUser($userId));
        }

        return $this->addFormatsAndUrl($media);
    }

    /**
     * @return array<string, mixed>
     */
    private function getProperties(UploadedFile $uploadedFile)
    {
        return $this->propertiesProvider->provide($uploadedFile);
    }

    /**
     * Modifies an existing media.
     *
     * @param UploadedFile $uploadedFile
     * @param array $data
     * @param UserInterface $user
     *
     * @throws FileVersionNotFoundException
     * @throws InvalidMediaTypeException
     *
     * @return Media
     */
    private function modifyMedia($uploadedFile, $data, $user)
    {
        $mediaEntity = $this->getEntityById($data['id']);
        $mediaEntity->setChanger($user);
        $mediaEntity->setChanged(new \DateTime());

        $files = $mediaEntity->getFiles();
        if (!isset($files[0])) {
            throw new FileNotFoundException('File was not found in media entity with the id . ' . $data['id']);
        }

        /** @var File $file */
        $file = $files[0]; // currently a media can only have one file

        $file->setChanger($user);
        $file->setChanged(new \DateTime());

        $version = $file->getVersion();

        $currentFileVersion = $file->getFileVersion($version);

        if (!$currentFileVersion) {
            throw new FileVersionNotFoundException($mediaEntity->getId(), $version);
        }

        if ($uploadedFile) {
            // new uploaded file
            ++$version;
            $this->validator->validate($uploadedFile);
            $type = $this->typeManager->getMediaType($uploadedFile->getMimeType());
            if ($type !== $mediaEntity->getType()->getId()) {
                throw new InvalidMediaTypeException('New media version must have the same media type.');
            }

            $data['storageOptions'] = $this->storage->save(
                $uploadedFile->getPathname(),
                $this->getNormalizedFileName($uploadedFile->getClientOriginalName()),
                $currentFileVersion->getStorageOptions()
            );
            $data['name'] = $uploadedFile->getClientOriginalName();
            $data['size'] = \intval($uploadedFile->getSize());
            $data['mimeType'] = $uploadedFile->getMimeType();
            $data['properties'] = $this->getProperties($uploadedFile);
            $data['type'] = [
                'id' => $type,
            ];
            $data['version'] = $version;

            $fileVersion = clone $currentFileVersion;
            $this->em->persist($fileVersion);

            $fileVersion->setChanged(new \DateTime());
            $fileVersion->setChanger($user);
            $fileVersion->setCreated(new \DateTime());
            $fileVersion->setCreator($user);
            $fileVersion->setDownloadCounter(0);

            $file->setVersion($version);
            $fileVersion->setVersion($version);
            $fileVersion->setFile($file);
            $file->addFileVersion($fileVersion);

            // delete old fileversion from cache
            $this->formatManager->purge(
                $mediaEntity->getId(),
                $currentFileVersion->getName(),
                $currentFileVersion->getMimeType()
            );
        } else {
            // not setable in update
            unset($data['name']);
            unset($data['size']);
            unset($data['type']);
            unset($data['version']);
            unset($data['mimeType']);
            unset($data['storageOptions']);
            $data['changed'] = \date('Y-m-d H:i:s');

            if ((isset($data['focusPointX']) && $data['focusPointX'] != $currentFileVersion->getFocusPointX())
                || (isset($data['focusPointY']) && $data['focusPointY'] != $currentFileVersion->getFocusPointY())
            ) {
                $currentFileVersion->increaseSubVersion();
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $currentFileVersion->getName(),
                    $currentFileVersion->getMimeType()
                );
            }
        }

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $this->em->persist($media->getEntity());
        $this->em->flush();

        return $media;
    }

    /**
     * Prepares data.
     *
     * @param UploadedFile $uploadedFile
     * @param array $data
     * @param UserInterface $user
     *
     * @return Media
     *
     * @throws InvalidFileException
     */
    private function buildData($uploadedFile, $data, $user)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new InvalidFileException('Given uploaded file is not of instance UploadedFile');
        }

        $this->validator->validate($uploadedFile);

        $data['storageOptions'] = $this->storage->save(
            $uploadedFile->getPathname(),
            $this->getNormalizedFileName($uploadedFile->getClientOriginalName())
        );

        $data['name'] = $uploadedFile->getClientOriginalName();
        $data['size'] = $uploadedFile->getSize();
        $data['mimeType'] = $uploadedFile->getMimeType();
        $data['properties'] = $this->getProperties($uploadedFile);
        $data['type'] = [
            'id' => $this->typeManager->getMediaType($uploadedFile->getMimeType()),
        ];

        return $this->createMedia($data, $user);
    }

    /**
     * Create a new media.
     *
     * @param array $data
     * @param UserInterface $user
     *
     * @return Media
     */
    protected function createMedia($data, $user)
    {
        $mediaEntity = $this->mediaRepository->createNew();
        $mediaEntity->setCreator($user);
        $mediaEntity->setChanger($user);

        $file = new File();
        $file->setCreator($user);
        $file->setChanger($user);
        $file->setVersion(1);
        $file->setMedia($mediaEntity);

        $fileVersion = new FileVersion();
        $fileVersion->setCreator($user);
        $fileVersion->setChanger($user);
        $fileVersion->setVersion(1);
        $fileVersion->setFile($file);

        $file->addFileVersion($fileVersion);
        $mediaEntity->addFile($file);

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $fileVersion->setDefaultMeta($fileVersion->getMeta()->first());

        $mediaEntity = $media->getEntity();
        $this->em->persist($mediaEntity);
        $this->em->flush();

        return $media;
    }

    /**
     * Data can be set over by array.
     *
     * @param array $data
     * @param UserInterface $user
     *
     * @return Media
     */
    protected function setDataToMedia(Media $media, $data, $user)
    {
        foreach ($data as $attribute => $value) {
            if ($value ||
                'tags' === $attribute ||
                'size' === $attribute ||
                'description' === $attribute ||
                'copyright' === $attribute ||
                'credits' === $attribute ||
                'categories' === $attribute ||
                'targetGroups' === $attribute ||
                'focusPointX' === $attribute ||
                'focusPointY' === $attribute
            ) {
                switch ($attribute) {
                    case 'size':
                        $media->setSize($value);
                        break;
                    case 'title':
                        $media->setTitle($value);
                        break;
                    case 'description':
                        $media->setDescription($value);
                        break;
                    case 'copyright':
                        $media->setCopyright($value);
                        break;
                    case 'credits':
                        $media->setCredits($value);
                        break;
                    case 'version':
                        $media->setVersion($value);
                        break;
                    case 'name':
                        $media->setName($value);
                        break;
                    case 'url':
                        $media->setUrl($value);
                        break;
                    case 'formats':
                        $media->setFormats($value);
                        break;
                    case 'storageOptions':
                        $media->setStorageOptions($value);
                        break;
                    case 'publishLanguages':
                        $media->setPublishLanguages($value);
                        break;
                    case 'contentLanguages':
                        $media->setContentLanguages($value);
                        break;
                    case 'tags':
                        $media->removeTags();
                        if (\count($value)) {
                            foreach ($value as $tag) {
                                $tagEntity = $this->tagManager->findOrCreateByName($tag, $user->getId());
                                $media->addTag($tagEntity);
                            }
                        }
                        break;
                    case 'properties':
                        $media->setProperties($value);
                        break;
                    case 'changed':
                        $media->setChanged($value);
                        break;
                    case 'created':
                        break;
                    case 'changer':
                        if ($value instanceof UserInterface) {
                            $media->setChanger($value);
                        }
                        break;
                    case 'creator':
                        if ($value instanceof UserInterface) {
                            $media->setCreator($value);
                        }
                        break;
                    case 'mimeType':
                        $media->setMimeType($value);
                        break;
                    case 'collection':
                        $collectionEntity = $this->getCollectionById($value);
                        $media->setCollection($collectionEntity); // set parent
                        break;
                    case 'type':
                        if (isset($value['id'])) {
                            $type = $this->typeManager->get($value['id']);
                            $media->setType($type);
                        }
                        break;
                    case 'categories':
                        $categoryIds = $value;
                        $media->removeCategories();

                        if (\is_array($categoryIds) && !empty($categoryIds)) {
                            $categories = $this->categoryRepository->findCategoriesByIds($categoryIds);

                            foreach ($categories as $category) {
                                $media->addCategory($category);
                            }
                        }
                        break;
                    case 'targetGroups':
                        $targetGroupIds = $value;
                        $media->removeTargetGroups();

                        if (\is_array($targetGroupIds) && !empty($targetGroupIds)) {
                            $targetGroups = $this->targetGroupRepository->findByIds($targetGroupIds);

                            foreach ($targetGroups as $targetGroup) {
                                $media->addTargetGroup($targetGroup);
                            }
                        }
                        break;
                    case 'focusPointX':
                        $media->setFocusPointX($value);
                        break;
                    case 'focusPointY':
                        $media->setFocusPointY($value);
                        break;
                }
            }
        }

        return $media;
    }

    /**
     * @param int $collectionId
     *
     * @return object
     *
     * @throws CollectionNotFoundException
     */
    public function getCollectionById($collectionId)
    {
        $collection = $this->collectionRepository->find($collectionId);
        if (!$collection) {
            throw new CollectionNotFoundException($collectionId);
        }

        return $collection;
    }

    public function delete($id, $checkSecurity = false)
    {
        $mediaEntity = $this->getEntityById($id);

        if ($checkSecurity) {
            $this->securityChecker->checkPermission(
                new SecurityCondition(
                    'sulu.media.collections',
                    null,
                    Collection::class,
                    $mediaEntity->getCollection()->getId()
                ),
                PermissionTypes::DELETE
            );
        }

        /** @var File $file */
        foreach ($mediaEntity->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $fileVersion->getName(),
                    $fileVersion->getMimeType()
                );

                $this->storage->remove($fileVersion->getStorageOptions());

                foreach ($fileVersion->getMeta() as $fileVersionMeta) {
                    // this will trigger massive-search deindex
                    $this->em->remove($fileVersionMeta);
                }
                foreach ($fileVersion->getFormatOptions() as $formatOptions) {
                    $this->em->detach($formatOptions);
                }
                $this->em->detach($fileVersion);
            }
            $this->em->detach($file);
        }

        $this->em->remove($mediaEntity);
        $this->em->flush();
    }

    public function move($id, $locale, $destCollection)
    {
        try {
            $mediaEntity = $this->mediaRepository->findMediaById($id);

            if (null === $mediaEntity) {
                throw new MediaNotFoundException($id);
            }

            $mediaEntity->setCollection($this->em->getReference(self::ENTITY_NAME_COLLECTION, $destCollection));

            $this->em->flush();

            return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        } catch (DBALException $ex) {
            throw new CollectionNotFoundException($destCollection);
        }
    }

    public function increaseDownloadCounter($fileVersionId)
    {
        $query = $this->em->createQueryBuilder()->update('SuluMediaBundle:FileVersion', 'fV')
            ->set('fV.downloadCounter', 'fV.downloadCounter + 1')
            ->where('fV.id = :id')
            ->setParameter('id', $fileVersionId)
            ->getQuery();

        $query->execute();
    }

    public function getFormatUrls($ids, $locale)
    {
        $mediaArray = $this->getByIds($ids, $locale);
        $formatUrls = [];
        foreach ($mediaArray as $media) {
            if ($media->getEntity()->getPreviewImage()) {
                $previewImage = new Media($media->getEntity()->getPreviewImage(), $locale);

                $formatUrls[$media->getId()] = $this->formatManager->getFormats(
                    $previewImage->getId(),
                    $previewImage->getName(),
                    $previewImage->getVersion(),
                    $previewImage->getSubVersion(),
                    $previewImage->getMimeType()
                );
            } else {
                $formatUrls[$media->getId()] = $this->formatManager->getFormats(
                    $media->getId(),
                    $media->getName(),
                    $media->getVersion(),
                    $media->getSubVersion(),
                    $media->getMimeType()
                );
            }
        }

        return $formatUrls;
    }

    /**
     * @return Media
     */
    public function addFormatsAndUrl(Media $media)
    {
        // Get preview image and set either preview thumbnails if set, else rendered images
        /** @var \Sulu\Bundle\MediaBundle\Entity\MediaInterface $previewImage */
        $previewImage = $media->getEntity()->getPreviewImage();

        if (null !== $previewImage) {
            /** @var FileVersion $latestVersion */
            $latestVersion = null;

            /** @var File $file */
            foreach ($previewImage->getFiles() as $file) {
                $latestVersion = $file->getLatestFileVersion();

                // currently only one file per media exists
                break;
            }

            if (null !== $latestVersion) {
                $media->setFormats(
                    $this->formatManager->getFormats(
                        $previewImage->getId(),
                        $latestVersion->getName(),
                        $latestVersion->getVersion(),
                        $latestVersion->getSubVersion(),
                        $latestVersion->getMimeType()
                    )
                );
            }
        } else {
            $media->setFormats(
                $this->formatManager->getFormats(
                    $media->getId(),
                    $media->getName(),
                    $media->getVersion(),
                    $media->getSubVersion(),
                    $media->getMimeType()
                )
            );
        }

        // Set Version Urls
        $versionData = [];
        foreach ($media->getFile()->getFileVersions() as $fileVersion) {
            $versionData[$fileVersion->getVersion()] = [];
            $versionData[$fileVersion->getVersion()]['url'] = $this->getUrl(
                $media->getId(),
                $fileVersion->getName(),
                $fileVersion->getVersion()
            );
            $versionData[$fileVersion->getVersion()]['adminUrl'] = $this->getAdminUrl(
                $media->getId(),
                $fileVersion->getName(),
                $fileVersion->getVersion()
            );
        }

        $media->setAdditionalVersionData($versionData);

        // set properties
        $properties = $media->getFileVersion()->getProperties();
        if (null !== $properties) {
            $media->setProperties($properties);
        }

        // Set Current Url
        if (isset($versionData[$media->getVersion()], $versionData[$media->getVersion()]['url'])) {
            $media->setUrl($versionData[$media->getVersion()]['url']);
            $media->setAdminUrl($versionData[$media->getVersion()]['adminUrl']);
        }

        return $media;
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param int $userId
     *
     * @return UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * Generate url by given path.
     *
     * @param string|int $id
     * @param string|int $version
     */
    private function generateUrl(string $path, $id, string $fileName, $version): string
    {
        return \str_replace(
                [
                    '{id}',
                    '{slug}',
                ],
                [
                    $id,
                    \rawurlencode($fileName),
                ],
                $path
            ) . '?v=' . $version;
    }

    public function getUrl($id, $fileName, $version)
    {
        return $this->generateUrl($this->downloadPath, $id, $fileName, $version);
    }

    public function getAdminUrl($id, $fileName, $version)
    {
        return $this->generateUrl($this->adminDownloadPath, $id, $fileName, $version);
    }

    /**
     * Returns current user or null if no user is loggedin.
     *
     * @return UserInterface|void
     */
    protected function getCurrentUser()
    {
        if (!$this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        return;
    }

    /**
     * Returns file name without special characters and preserves file extension.
     *
     * @param string $originalFileName
     *
     * @return string
     */
    private function getNormalizedFileName($originalFileName)
    {
        if (false !== \strpos($originalFileName, '.')) {
            $pathParts = \pathinfo($originalFileName);
            $fileName = $this->pathCleaner->cleanup($pathParts['filename']);
            $fileName .= '.' . $pathParts['extension'];
        } else {
            $fileName = $this->pathCleaner->cleanup($originalFileName);
        }

        return $fileName;
    }
}
