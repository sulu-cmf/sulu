<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes media available through a REST API.
 */
class MediaController extends AbstractMediaController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private MediaManagerInterface $mediaManager,
        private FormatManagerInterface $formatManager,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        private EntityManagerInterface $entityManager,
        private StorageInterface $storage,
        private CollectionRepositoryInterface $collectionRepository,
        private SecurityCheckerInterface $securityChecker,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private string $mediaClass,
        private string $collectionClass,
        private MediaListBuilderFactory $mediaListBuilderFactory,
        private MediaListRepresentationFactory $mediaListRepresentationFactory
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    /**
     * Shows a single media with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $view = $this->responseGetById(
                $id,
                function($id) use ($locale) {
                    $media = $this->mediaManager->getById($id, $locale);
                    $collection = $media->getEntity()->getCollection();

                    if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collection->getType()->getKey()) {
                        $this->securityChecker->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    $this->securityChecker->checkPermission(
                        new SecurityCondition(
                            $this->getSecurityContext(),
                            $locale,
                            $this->getSecuredClass(),
                            $collection->getId()
                        ),
                        PermissionTypes::VIEW
                    );

                    return $media;
                }
            );
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Lists all media.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        /** @var UserInterface $user */
        $user = $this->getUser();
        $types = \array_filter(\explode(',', $request->get('types')));
        $collectionId = $request->get('collection');
        $collectionId = $collectionId ? (int) $collectionId : null;
        $locale = $this->getRequestParameter($request, 'locale', true);

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('media');
        $listBuilder = $this->mediaListBuilderFactory->getListBuilder(
            $fieldDescriptors,
            $user,
            $types,
            !$request->get('sortBy'),
            $collectionId
        );

        $listRepresentation = $this->mediaListRepresentationFactory->getListRepresentation(
            $listBuilder,
            $locale,
            MediaInterface::RESOURCE_KEY,
            'sulu_media.cget_media',
            $request->query->all()
        );

        $view = $this->view($listRepresentation, 200);

        return $this->handleView($view);
    }

    /**
     * Returns a list-builder for media list.
     *
     * @deprecated
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     *
     * @return DoctrineListBuilder
     */
    private function getListBuilder(Request $request, array $fieldDescriptors, array $types)
    {
        $listBuilder = $this->doctrineListBuilderFactory->create($this->mediaClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // default sort by created
        if (!$request->get('sortBy')) {
            $listBuilder->sort($fieldDescriptors['created'], 'desc');
        }

        $collectionId = $request->get('collection');
        if ($collectionId) {
            $collectionType = $this->collectionRepository->findCollectionTypeById($collectionId);
            if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collectionType) {
                $this->securityChecker->checkPermission(
                    'sulu.media.system_collections',
                    PermissionTypes::VIEW
                );
            }
            $listBuilder->addSelectField($fieldDescriptors['collection']);
            $listBuilder->where($fieldDescriptors['collection'], $collectionId);
        } else {
            $listBuilder->addPermissionCheckField($fieldDescriptors['collection']);
            $listBuilder->setPermissionCheck(
                $this->getUser(),
                PermissionTypes::VIEW,
                $this->collectionClass
            );
        }

        // set the types
        if (\count($types)) {
            $listBuilder->in($fieldDescriptors['type'], $types);
        }

        if (!$this->securityChecker->hasPermission('sulu.media.system_collections', PermissionTypes::VIEW)) {
            $systemCollection = $this->collectionRepository
                ->findCollectionByKey(SystemCollectionManagerInterface::COLLECTION_KEY);

            $lftExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['lft'],
                $systemCollection->getLft(),
                ListBuilderInterface::WHERE_COMPARATOR_LESS
            );
            $rgtExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['rgt'],
                $systemCollection->getRgt(),
                ListBuilderInterface::WHERE_COMPARATOR_GREATER
            );

            $listBuilder->addExpression(
                $listBuilder->createOrExpression([
                    $lftExpression,
                    $rgtExpression,
                ])
            );
        }

        // field which will be needed afterwards to generate route
        $listBuilder->addSelectField($fieldDescriptors['previewImageId']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageName']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageVersion']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageSubVersion']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageMimeType']);
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->addSelectField($fieldDescriptors['subVersion']);
        $listBuilder->addSelectField($fieldDescriptors['name']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['mimeType']);
        $listBuilder->addSelectField($fieldDescriptors['storageOptions']);
        $listBuilder->addSelectField($fieldDescriptors['id']);
        $listBuilder->addSelectField($fieldDescriptors['collection']);

        return $listBuilder;
    }

    /**
     * Creates a new media.
     *
     * @return Response
     *
     * @throws CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing media with the given id.
     *
     * @param int $id The id of the media to update
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a media with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            try {
                $this->mediaManager->delete($id, true);
            } catch (MediaNotFoundException $e) {
                throw new EntityNotFoundException($this->mediaClass, $id, $e); // will throw 404 Entity not found
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param int $id
     * @param string $version
     *
     * @return Response
     *
     * @throws MissingParameterException
     */
    public function deleteVersionAction($id, $version)
    {
        $this->mediaManager->removeFileVersion((int) $id, (int) $version);

        return new Response('', 204);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @param int $id
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            return match ($action) {
                'move' => $this->moveEntity($id, $request),
                'new-version' => $this->saveEntity($id, $request),
                default => throw new RestException(\sprintf('Unrecognized action: "%s"', $action)),
            };
        } catch (RestException $e) {
            $view = $this->view($e->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Move an entity to another collection.
     *
     * @param int $id
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $destination = $this->getRequestParameter($request, 'destination', true);

            $media = $this->mediaManager->move(
                $id,
                $locale,
                $destination
            );

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @param int|null $id
     *
     * @return Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $data = $this->getData($request, null === $id);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $media = $this->mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @return string
     */
    public function getSecurityContext()
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    /**
     * Returns the class name of the object to check.
     *
     * @return string
     */
    public function getSecuredClass()
    {
        // The media permissions are tied to the collection it is in
        return Collection::class;
    }

    /**
     * Returns the id of the object to check.
     *
     * @return string
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('collection');
    }
}
