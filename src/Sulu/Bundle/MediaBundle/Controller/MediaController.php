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
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
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

    /**
     * @deprecated Use the MediaInterface::RESOURCE_KEY constant instead
     *
     * @var string
     */
    protected static $entityKey = MediaInterface::RESOURCE_KEY;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $doctrineListBuilderFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var CollectionRepositoryInterface
     */
    private $collectionRepository;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var string
     */
    private $mediaClass;

    /**
     * @var string
     */
    private $collectionClass;

    /**
     * @var MediaListBuilderFactory|null
     */
    private $mediaListBuilderFactory;

    /**
     * @var MediaListRepresentationFactory|null
     */
    private $mediaListRepresentationFactory;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        MediaManagerInterface $mediaManager,
        FormatManagerInterface $formatManager,
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        EntityManagerInterface $entityManager,
        StorageInterface $storage,
        CollectionRepositoryInterface $collectionRepository,
        SecurityCheckerInterface $securityChecker,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        string $mediaClass,
        string $collectionClass,
        ?MediaListBuilderFactory $mediaListBuilderFactory = null,
        ?MediaListRepresentationFactory $mediaListRepresentationFactory = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->mediaManager = $mediaManager;
        $this->formatManager = $formatManager;
        $this->restHelper = $restHelper;
        $this->doctrineListBuilderFactory = $doctrineListBuilderFactory;
        $this->entityManager = $entityManager;
        $this->storage = $storage;
        $this->collectionRepository = $collectionRepository;
        $this->securityChecker = $securityChecker;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->mediaClass = $mediaClass;
        $this->collectionClass = $collectionClass;
        $this->mediaListBuilderFactory = $mediaListBuilderFactory;
        $this->mediaListRepresentationFactory = $mediaListRepresentationFactory;

        if (null === $this->mediaListBuilderFactory || null === $this->mediaListRepresentationFactory) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.3',
                'Instantiating MediaController without the $mediaListBuilderFactory or $mediaListRepresentationFactory argument is deprecated.'
            );
        }
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
        if (null === $this->mediaListBuilderFactory || null === $this->mediaListRepresentationFactory) {
            $listRepresentation = $this->getListRepresentation($request);
        } else {
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
        }

        $view = $this->view($listRepresentation, 200);

        return $this->handleView($view);
    }

    /**
     * @deprecated
     */
    private function getListRepresentation(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('media');
        $types = \array_filter(\explode(',', $request->get('types')));
        $listBuilder = $this->getListBuilder($request, $fieldDescriptors, $types);
        $listBuilder->setParameter('locale', $locale);
        $listResponse = $listBuilder->execute();

        for ($i = 0, $length = \count($listResponse); $i < $length; ++$i) {
            $format = $this->formatManager->getFormats(
                $listResponse[$i]['previewImageId'] ?? $listResponse[$i]['id'],
                $listResponse[$i]['previewImageName'] ?? $listResponse[$i]['name'],
                $listResponse[$i]['previewImageVersion'] ?? $listResponse[$i]['version'],
                $listResponse[$i]['previewImageSubVersion'] ?? $listResponse[$i]['subVersion'],
                $listResponse[$i]['previewImageMimeType'] ?? $listResponse[$i]['mimeType']
            );

            if (0 < \count($format)) {
                $listResponse[$i]['thumbnails'] = $format;
            }

            $listResponse[$i]['url'] = $this->mediaManager->getUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );

            $listResponse[$i]['adminUrl'] = $this->mediaManager->getAdminUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );

            if ($locale !== $listResponse[$i]['locale']) {
                $listResponse[$i]['ghostLocale'] = $listResponse[$i]['locale'];
            }
        }

        $ids = $listBuilder->getIds();
        if (null != $ids) {
            $result = [];
            foreach ($listResponse as $item) {
                $result[\array_search($item['id'], $ids)] = $item;
            }
            \ksort($result);
            $listResponse = \array_values($result);
        }

        return new ListRepresentation(
            $listResponse,
            MediaInterface::RESOURCE_KEY,
            'sulu_media.cget_media',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    /**
     * Returns a list-builder for media list.
     *
     * @deprecated
     *
     * @param FieldDescriptorInterface[] $fieldDescriptors
     * @param array $types
     *
     * @return DoctrineListBuilder
     */
    private function getListBuilder(Request $request, array $fieldDescriptors, $types)
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
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
     * @return \Symfony\Component\HttpFoundation\Response
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
     * @throws \Sulu\Component\Rest\Exception\MissingParameterException
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
            switch ($action) {
                case 'move':
                    return $this->moveEntity($id, $request);
                    break;
                case 'new-version':
                    return $this->saveEntity($id, $request);
                    break;
                default:
                    throw new RestException(\sprintf('Unrecognized action: "%s"', $action));
            }
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
     * @return \Symfony\Component\HttpFoundation\Response
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
