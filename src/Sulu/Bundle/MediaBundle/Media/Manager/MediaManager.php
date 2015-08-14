<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Default implementation of media manager.
 */
class MediaManager implements MediaManagerInterface
{
    const ENTITY_NAME_MEDIA = 'SuluMediaBundle:Media';
    const ENTITY_NAME_COLLECTION = 'SuluMediaBundle:Collection';
    const ENTITY_NAME_FILE = 'SuluMediaBundle:File';
    const ENTITY_NAME_FILEVERSION = 'SuluMediaBundle:FileVersion';
    const ENTITY_NAME_FILEVERSIONMETA = 'SuluMediaBundle:FileVersionMeta';
    const ENTITY_NAME_TAG = 'SuluTagBundle:Tag';
    const ENTITY_NAME_FILEVERSIONCONTENTLANGUAGE = 'SuluMediaBundle:FileVersionContentLanguage';
    const ENTITY_NAME_FILEVERSIONPUBLISHLANGUAGE = 'SuluMediaBundle:FileVersionPublishLanguage';

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
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors = [];

    /**
     * @var string
     */
    private $downloadPath;

    /**
     * @var int
     */
    public $count;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param CollectionRepositoryInterface $collectionRepository
     * @param UserRepositoryInterface $userRepository
     * @param EntityManager $em
     * @param StorageInterface $storage
     * @param FileValidatorInterface $validator
     * @param FormatManagerInterface $formatManager
     * @param TagManagerInterface $tagManager
     * @param TypeManagerInterface $typeManager
     * @param string $downloadPath
     * @param string $maxFileSize
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        CollectionRepositoryInterface $collectionRepository,
        UserRepositoryInterface $userRepository,
        EntityManager $em,
        StorageInterface $storage,
        FileValidatorInterface $validator,
        FormatManagerInterface $formatManager,
        TagManagerInterface $tagManager,
        TypeManagerInterface $typeManager,
        $downloadPath,
        $maxFileSize
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->collectionRepository = $collectionRepository;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->formatManager = $formatManager;
        $this->typeManager = $typeManager;
        $this->downloadPath = $downloadPath;
        $this->maxFileSize = $maxFileSize;
        $this->tagManager = $tagManager;

        $this->initializeFieldDescriptors();
    }

    /**
     * TODO.
     *
     * @return array
     */
    private function initializeFieldDescriptors()
    {
        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::ENTITY_NAME_MEDIA,
            'public.id',
            [],
            true,
            false
        );

        $fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'id',
            'thumbnails',
            self::ENTITY_NAME_MEDIA,
            'media.media.thumbnails',
            [],
            false,
            true,
            'thumbnails'
        );

        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::ENTITY_NAME_FILEVERSION,
            'public.name',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
            ]
        );
        $fieldDescriptors['size'] = new DoctrineFieldDescriptor(
            'size',
            'size',
            self::ENTITY_NAME_FILEVERSION,
            'media.media.size',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
            ],
            false,
            true,
            'bytes'
        );

        $fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::ENTITY_NAME_FILEVERSION,
            'public.changed',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
            ],
            true,
            false,
            'date'
        );

        $fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::ENTITY_NAME_FILEVERSION,
            'public.created',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
            ],
            true,
            false,
            'date'
        );

        $fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::ENTITY_NAME_FILEVERSIONMETA,
            'public.title',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
                self::ENTITY_NAME_FILEVERSIONMETA => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSIONMETA,
                    self::ENTITY_NAME_FILEVERSION . '.meta'
                ),
            ],
            false,
            true,
            'title'
        );

        $fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::ENTITY_NAME_FILEVERSIONMETA,
            'media.media.description',
            [
                self::ENTITY_NAME_FILE => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILE,
                    self::ENTITY_NAME_MEDIA . '.files'
                ),
                self::ENTITY_NAME_FILEVERSION => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSION,
                    self::ENTITY_NAME_FILE . '.fileVersions',
                    self::ENTITY_NAME_FILEVERSION . '.version = ' . self::ENTITY_NAME_FILE . '.version'
                ),
                self::ENTITY_NAME_FILEVERSIONMETA => new DoctrineJoinDescriptor(
                    self::ENTITY_NAME_FILEVERSIONMETA,
                    self::ENTITY_NAME_FILEVERSION . '.meta'
                ),
            ]
        );

        $this->fieldDescriptors = $fieldDescriptors;

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptors()
    {
        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id, $locale)
    {
        $mediaEntity = $this->getEntityById($id);

        return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityById($id)
    {
        $mediaEntity = $this->mediaRepository->findMediaById($id);
        if (!$mediaEntity) {
            throw new MediaNotFoundException('Media with the ID ' . $id . ' was not found.');
        }

        return $mediaEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function getByIds(array $ids, $locale)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia(['pagination' => false, 'ids' => $ids]);
        $this->count = count($mediaEntities);
        foreach ($mediaEntities as $mediaEntity) {
            $media[array_search($mediaEntity->getId(), $ids)] = $this->addFormatsAndUrl(
                new Media($mediaEntity, $locale, null)
            );
        }

        ksort($media);

        return array_values($media);
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $filter = [], $limit = null, $offset = null)
    {
        $media = [];
        $mediaEntities = $this->mediaRepository->findMedia($filter, $limit, $offset);
        $this->count = $this->mediaRepository->count($filter);

        foreach ($mediaEntities as $mediaEntity) {
            $media[] = $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        }

        return $media;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
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
     * Modifies an existing media.
     *
     * @param UploadedFile $uploadedFile
     * @param $data
     * @param UserInterface $user
     *
     * @throws MediaNotFoundException
     * @throws FileVersionNotFoundException
     * @throws FileNotFoundException
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

        $currentFileVersion = null;

        foreach ($file->getFileVersions() as $fileVersion) {
            /**  @var FileVersion $fileVersion */
            if ($version == $fileVersion->getVersion()) {
                $currentFileVersion = $fileVersion;
                break;
            }
        }

        if (!$currentFileVersion) {
            throw new FileVersionNotFoundException($mediaEntity->getId(), $version);
        }

        if ($uploadedFile) {
            // new uploaded file
            ++$version;
            $this->validator->validate($uploadedFile);

            $data['storageOptions'] = $this->storage->save(
                $uploadedFile->getPathname(),
                $uploadedFile->getClientOriginalName(),
                $version,
                $currentFileVersion->getStorageOptions()
            );
            $data['name'] = $uploadedFile->getClientOriginalName();
            $data['size'] = intval($uploadedFile->getSize());
            $data['mimeType'] = $uploadedFile->getMimeType();
            $data['type'] = [
                'id' => $this->typeManager->getMediaType($uploadedFile->getMimeType()),
            ];
            $data['version'] = $version;

            $fileVersion = clone($currentFileVersion);
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
                $currentFileVersion->getStorageOptions()
            );
        } else {
            // not setable in update
            $data['name'] = null;
            $data['size'] = null;
            $data['type'] = null;
            $data['version'] = null;
            $data['mimeType'] = null;
            $data['storageOptions'] = null;
            $data['changed'] = date('Y-m-d H:i:s');
        }

        $media = new Media($mediaEntity, $data['locale'], null);

        $media = $this->setDataToMedia(
            $media,
            $data,
            $user
        );

        $mediaEntity = $media->getEntity();
        $this->em->persist($mediaEntity);
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
            $uploadedFile->getClientOriginalName(),
            1
        );
        $data['name'] = $uploadedFile->getClientOriginalName();
        $data['size'] = $uploadedFile->getSize();
        $data['mimeType'] = $uploadedFile->getMimeType();
        $data['type'] = [
            'id' => $this->typeManager->getMediaType($uploadedFile->getMimeType()),
        ];

        return $this->createMedia($data, $user);
    }

    /**
     * Create a new media.
     *
     * @param $data
     * @param $user
     *
     * @return Media
     */
    protected function createMedia($data, $user)
    {
        $mediaEntity = new MediaEntity();
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
     * @param $media
     * @param $data
     * @param UserInterface $user
     *
     * @return Media
     */
    protected function setDataToMedia(Media $media, $data, $user)
    {
        foreach ($data as $attribute => $value) {
            if ($value || ($attribute === 'tags' && $value !== null) || ($attribute === 'size' && $value !== null)) {
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
                        if (count($value)) {
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
                }
            }
        }

        return $media;
    }

    /**
     * @param $collectionId
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

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $mediaEntity = $this->getEntityById($id);

        /** @var File $file */
        foreach ($mediaEntity->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $this->formatManager->purge(
                    $mediaEntity->getId(),
                    $fileVersion->getName(),
                    $fileVersion->getStorageOptions()
                );
            }
        }

        $this->em->remove($mediaEntity);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $locale, $destCollection)
    {
        try {
            $mediaEntity = $this->mediaRepository->findMediaById($id);

            if ($mediaEntity === null) {
                throw new MediaNotFoundException($id);
            }

            $mediaEntity->setCollection($this->em->getReference(self::ENTITY_NAME_COLLECTION, $destCollection));

            $this->em->flush();

            return $this->addFormatsAndUrl(new Media($mediaEntity, $locale, null));
        } catch (DBALException $ex) {
            throw new CollectionNotFoundException($destCollection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function increaseDownloadCounter($fileVersionId)
    {
        $query = $this->em->createQueryBuilder()->update('SuluMediaBundle:FileVersion', 'fV')
            ->set('fV.downloadCounter', 'fV.downloadCounter + 1')
            ->where('fV.id = :id')
            ->setParameter('id', $fileVersionId)
            ->getQuery();

        $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatUrls($ids, $locale)
    {
        $mediaArray = $this->getByIds($ids, $locale);
        $formatUrls = [];
        foreach ($mediaArray as $media) {
            array_push($formatUrls, $this->formatManager->getFormats(
                $media->getId(),
                $media->getName(),
                $media->getStorageOptions(),
                $media->getVersion(),
                $media->getMimeType()
            ));
        }

        return $formatUrls;
    }

    /**
     * @param Media $media
     *
     * @return Media
     */
    public function addFormatsAndUrl(Media $media)
    {
        // Set Formats
        $media->setFormats(
            $this->formatManager->getFormats(
                $media->getId(),
                $media->getName(),
                $media->getStorageOptions(),
                $media->getVersion(),
                $media->getMimeType()
            )
        );

        // Set Version Urls
        $versionData = [];
        foreach ($media->getFile()->getFileVersions() as $fileVersion) {
            $versionData[$fileVersion->getVersion()] = [];
            $versionData[$fileVersion->getVersion()]['url'] = $this->getUrl(
                $media->getId(),
                $fileVersion->getName(),
                $fileVersion->getVersion()
            );
        }

        $media->setAdditionalVersionData($versionData);

        // Set Current Url
        if (isset($versionData[$media->getVersion()])
            && isset($versionData[$media->getVersion()]['url'])
        ) {
            $media->setUrl($versionData[$media->getVersion()]['url']);
        }

        return $media;
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param $userId
     *
     * @return UserInterface
     */
    protected function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * @param $id
     * @param $fileName
     * @param $version
     *
     * @return string
     */
    protected function getUrl($id, $fileName, $version)
    {
        return str_replace(
            [
                '{id}',
                '{slug}',
            ],
            [
                $id,
                $fileName,
            ],
            $this->downloadPath
        ) . '?v=' . $version;
    }
}
