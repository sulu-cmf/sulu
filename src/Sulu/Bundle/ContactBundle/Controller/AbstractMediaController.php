<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes accounts available through a REST API.
 */
abstract class AbstractMediaController extends AbstractRestController
{
    protected static $collectionEntityName = \Sulu\Bundle\MediaBundle\Entity\Collection::class;

    protected static $fileVersionEntityName = \Sulu\Bundle\MediaBundle\Entity\FileVersion::class;

    protected static $fileEntityName = \Sulu\Bundle\MediaBundle\Entity\File::class;

    protected static $fileVersionMetaEntityName = \Sulu\Bundle\MediaBundle\Entity\FileVersionMeta::class;

    protected static $mediaEntityKey = 'media';

    protected $fieldDescriptors = null;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private EntityManagerInterface $entityManager,
        private MediaRepositoryInterface $mediaRepository,
        private MediaManagerInterface $mediaManager,
        private string $mediaClass,
        private ?MediaListBuilderFactory $mediaListBuilderFactory = null,
        private ?MediaListRepresentationFactory $mediaListRepresentationFactory = null,
        private ?FieldDescriptorFactoryInterface $fieldDescriptorFactory = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        if (null === $this->mediaListBuilderFactory || null === $this->mediaListRepresentationFactory || null === $this->fieldDescriptorFactory) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.3',
                'Instantiating AbstractMediaController without the $mediaListBuilderFactory, $mediaListRepresentationFactory or $fieldDescriptorFactory argument is deprecated.'
            );
        }
    }

    /**
     * Adds a relation between a media and the entity.
     *
     * @param string $entityName
     * @param string $id
     * @param string $mediaId
     * @param callable|null $dispatchDomainEventCallback
     *
     * @return Media
     */
    protected function addMediaToEntity($entityName, $id, $mediaId, $dispatchDomainEventCallback = null)
    {
        try {
            $em = $this->entityManager;
            $entity = $em->getRepository($entityName)->find($id);
            $media = $this->mediaRepository->find($mediaId);

            if (!$entity) {
                throw new EntityNotFoundException($entityName, $id);
            }

            if (!$media) {
                throw new EntityNotFoundException($this->mediaClass, $mediaId);
            }

            if ($entity->getMedias()->contains($media)) {
                throw new RestException('Relation already exists');
            }

            $entity->addMedia($media);

            if (null !== $dispatchDomainEventCallback) {
                $dispatchDomainEventCallback($entity, $media);
            }

            $em->flush();

            $view = $this->view(
                new Media(
                    $media,
                    $this->getUser()->getLocale(),
                    null
                ),
                200
            );
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Removes a media from the relation with an entity.
     *
     * @param string $entityName
     * @param string $id
     * @param string $mediaId
     * @param callable|null $dispatchDomainEventCallback
     *
     * @return Response
     */
    protected function removeMediaFromEntity($entityName, $id, $mediaId, $dispatchDomainEventCallback = null)
    {
        try {
            $delete = function() use ($entityName, $id, $mediaId, $dispatchDomainEventCallback) {
                $entity = $this->entityManager->getRepository($entityName)->find($id);
                $media = $this->mediaRepository->find($mediaId);

                if (!$entity) {
                    throw new EntityNotFoundException($entityName, $id);
                }

                if (!$media) {
                    throw new EntityNotFoundException($this->mediaClass, $mediaId);
                }

                if (!$entity->getMedias()->contains($media)) {
                    throw new RestException(
                        'Relation between ' . $entityName .
                        ' and ' . $this->mediaClass . ' with id ' . $mediaId . ' does not exists!'
                    );
                }

                $entity->removeMedia($media);

                if (null !== $dispatchDomainEventCallback) {
                    $dispatchDomainEventCallback($entity, $media);
                }

                $this->entityManager->flush();
            };

            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a view containing all media of an entity.
     *
     * @param string $entityName
     * @param string $routeName
     * @param Request $request
     *
     * @return Response
     */
    protected function getMultipleView($entityName, $routeName, AbstractContactManager $contactManager, $contactId, $request)
    {
        try {
            /** @var UserInterface $user */
            $user = $this->getUser();
            $locale = $this->getUser()->getLocale();

            if ('true' === $request->get('flat')) {
                if (null === $this->mediaListBuilderFactory
                    || null === $this->mediaListRepresentationFactory
                    || null === $this->fieldDescriptorFactory) {
                    $listRepresentation = $this->getListRepresentation(
                        $entityName,
                        $routeName,
                        $contactId,
                        $request,
                        $locale
                    );
                } else {
                    $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('media');

                    $fieldDescriptors['contactId'] = new DoctrineFieldDescriptor(
                        'id',
                        'contactId',
                        $entityName,
                        null,
                        [
                            $entityName => new DoctrineJoinDescriptor(
                                $entityName,
                                $entityName,
                                $entityName . '.id = :contactId'
                            ),
                            static::$mediaEntityKey => new DoctrineJoinDescriptor(
                                static::$mediaEntityKey,
                                $entityName . '.medias',
                                static::$mediaEntityKey . '.id = ' . $this->mediaClass . '.id',
                                DoctrineJoinDescriptor::JOIN_METHOD_INNER
                            ),
                        ],
                        FieldDescriptorInterface::VISIBILITY_NEVER,
                        FieldDescriptorInterface::SEARCHABILITY_NEVER
                    );

                    $listBuilder = $this->mediaListBuilderFactory->getListBuilder(
                        $fieldDescriptors,
                        $user,
                        [],
                        !$request->get('sortBy'),
                        null
                    );

                    $listBuilder->setParameter('contactId', $contactId);
                    $listBuilder->where($fieldDescriptors['contactId'], $contactId);

                    $listRepresentation = $this->mediaListRepresentationFactory->getListRepresentation(
                        $listBuilder,
                        $locale,
                        static::$mediaEntityKey,
                        $routeName,
                        \array_merge(['contactId' => $contactId], $request->query->all())
                    );
                }
            } else {
                $media = $contactManager->getById($contactId, $locale)->getMedias();
                $listRepresentation = new CollectionRepresentation($media, static::$mediaEntityKey);
            }

            $view = $this->view($listRepresentation, 200);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a list representation containing all media of an entity.
     *
     * @deprecated
     *
     * @param string $entityName
     * @param string $routeName
     * @param Request $request
     * @param string $locale
     *
     * @return ListRepresentation
     */
    private function getListRepresentation($entityName, $routeName, $contactId, $request, $locale)
    {
        $listBuilder = $this->listBuilderFactory->create($entityName);
        $fieldDescriptors = $this->getFieldDescriptors($entityName, $contactId);
        $listBuilder->setIdField($fieldDescriptors['id']);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listResponse = $listBuilder->execute();
        $listResponse = $this->addThumbnails($listResponse, $locale);
        $listResponse = $this->addUrls($listResponse, $locale);

        return new ListRepresentation(
            $listResponse,
            static::$mediaEntityKey,
            $routeName,
            \array_merge(['contactId' => $contactId], $request->query->all()),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    /**
     * Returns the field-descriptors. Ensures that the descriptors get only instantiated once.
     *
     * @deprecated
     *
     * @param string $entityName
     *
     * @return DoctrineFieldDescriptor[]
     */
    private function getFieldDescriptors($entityName, $id)
    {
        if (null === $this->fieldDescriptors) {
            $this->initFieldDescriptors($entityName, $id);
        }

        return $this->fieldDescriptors;
    }

    /**
     * Creates the array of field-descriptors.
     *
     * @deprecated
     *
     * @param string $entityName
     */
    private function initFieldDescriptors($entityName, $id)
    {
        $mediaEntityName = $this->mediaClass;

        $entityJoin = new DoctrineJoinDescriptor(
            $mediaEntityName,
            $entityName . '.medias',
            $entityName . '.id = ' . $id,
            DoctrineJoinDescriptor::JOIN_METHOD_INNER
        );

        $this->fieldDescriptors = [];

        $this->fieldDescriptors['entity'] = new DoctrineFieldDescriptor(
            'id',
            'entity',
            $entityName,
            null,
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            $mediaEntityName,
            'public.id',
            [
                $mediaEntityName => $entityJoin,
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );

        $this->fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'id',
            'thumbnails',
            $mediaEntityName,
            'media.media.thumbnails',
            [
                $mediaEntityName => $entityJoin,
            ],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'thumbnails',
            false
        );

        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$fileVersionEntityName,
            'public.name',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ]
        );
        $this->fieldDescriptors['size'] = new DoctrineFieldDescriptor(
            'size',
            'size',
            self::$fileVersionEntityName,
            'media.media.size',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'bytes'
        );

        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$fileVersionEntityName,
            'public.changed',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'date'
        );

        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$fileVersionEntityName,
            'public.created',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'date'
        );

        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$fileVersionMetaEntityName,
            'public.title',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
                self::$fileVersionMetaEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionMetaEntityName,
                    self::$fileVersionEntityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'title'
        );

        $this->fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::$fileVersionMetaEntityName,
            'media.media.description',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
                self::$fileVersionMetaEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionMetaEntityName,
                    self::$fileVersionEntityName . '.meta'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_YES
        );
    }

    /**
     * Takes an array of entities and resets the thumbnails-property containing the media id with
     * the actual urls to the thumbnails.
     *
     * @deprecated
     *
     * @param array $entities
     * @param string $locale
     *
     * @return array
     */
    private function addThumbnails($entities, $locale)
    {
        $ids = \array_filter(\array_column($entities, 'thumbnails'));
        $thumbnails = $this->mediaManager->getFormatUrls($ids, $locale);
        foreach ($entities as $key => $entity) {
            if (\array_key_exists('thumbnails', $entity)
                && $entity['thumbnails']
                && \array_key_exists($entity['thumbnails'], $thumbnails)
            ) {
                $entities[$key]['thumbnails'] = $thumbnails[$entity['thumbnails']];
            }
        }

        return $entities;
    }

    /**
     * Takes an array of entities and resets the url-property with the actual urls to the original file.
     *
     * @deprecated
     *
     * @param array $entities
     * @param string $locale
     *
     * @return array
     */
    private function addUrls($entities, $locale)
    {
        $ids = \array_filter(\array_column($entities, 'id'));
        $apiEntities = $this->mediaManager->getByIds($ids, $locale);
        $i = 0;
        foreach ($entities as $key => $entity) {
            $entities[$key]['url'] = $apiEntities[$i]->getUrl();
            ++$i;
        }

        return $entities;
    }
}
