<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\PropertyInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\Hash\RequestHashCheckerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterChoiceException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PageController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    public const WEBSPACE_NODE_SINGLE = 'single';

    public const WEBSPACE_NODES_ALL = 'all';

    /**
     * @deprecated Use the BasePageDocument::RESOURCE_KEY constant instead
     *
     * @var string
     */
    protected static $relationName = BasePageDocument::RESOURCE_KEY;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private SecurityCheckerInterface $securityChecker,
        private DocumentManagerInterface $documentManager,
        private ContentMapperInterface $contentMapper,
        private ContentRepositoryInterface $contentRepository,
        private RequestHashCheckerInterface $requestHashChecker,
        private WebspaceManagerInterface $webspaceManager,
        private SessionManagerInterface $sessionManager,
        private NodeRepositoryInterface $nodeRepository,
        private BaseMetadataFactory $metadataFactory,
        private FormFactoryInterface $formFactory
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function getAction(Request $request, string $id): Response
    {
        $locale = $this->getLocale($request);
        $complete = $this->getBooleanRequestParameter($request, 'complete', false, true);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);
        $template = $this->getRequestParameter($request, 'template', false, null);

        $view = $this->responseGetById(
            $id,
            function($id) use ($locale, $ghostContent, $template, $request) {
                try {
                    $document = $this->documentManager->find(
                        $id,
                        $locale,
                        [
                            'load_ghost_content' => $ghostContent,
                            'structure_type' => $template,
                        ]
                    );

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'view'
                    );

                    return $document;
                } catch (DocumentNotFoundException $ex) {
                    return;
                }
            }
        );

        $groups = [];
        if (!$complete) {
            $groups[] = 'smallPage';
        } else {
            $groups[] = 'defaultPage';
        }

        $context = new Context();
        $context->setGroups($groups);

        $view->setContext($context);

        return $this->handleView($view);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        // extract parameter
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);
        $userId = $this->getUser()->getId();

        // prepare vars
        $view = null;
        $data = null;

        try {
            switch ($action) {
                case 'move':
                    $data = $this->documentManager->find($id, $locale);

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $data),
                        'edit'
                    );

                    $this->documentManager->move(
                        $data,
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->documentManager->flush();
                    break;
                case 'copy':
                    $document = $this->documentManager->find($id, $locale);

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'edit'
                    );

                    $copiedPath = $this->documentManager->copy(
                        $document,
                        $this->getRequestParameter($request, 'destination', true)
                    );
                    $this->documentManager->flush();

                    $data = $this->documentManager->find($copiedPath, $locale);
                    break;
                case 'sort':
                    $document = $this->documentManager->find($id, $locale);
                    $data = $this->documentManager->sort($document, $locale);
                    break;
                case 'order':
                    $position = (int) $this->getRequestParameter($request, 'position', true);
                    $webspace = $this->getWebspace($request);

                    // call repository method
                    $data = $this->nodeRepository->orderAt($id, $position, $webspace, $locale, $userId);
                    break;
                case 'copy-locale':
                    $srcLocale = $this->getRequestParameter($request, 'src', false, $locale);
                    $destLocales = $this->getRequestParameter($request, 'dest', true);
                    $destLocales = \array_filter(\explode(',', $destLocales));

                    $document = $this->documentManager->find($id, $srcLocale);

                    foreach ($destLocales as $destLocale) {
                        $this->documentManager->copyLocale($document, $srcLocale, $destLocale);
                    }

                    $this->documentManager->flush();

                    $data = $document;
                    if ($locale !== $srcLocale) {
                        $data = $this->documentManager->find($id, $locale);
                    }
                    break;
                case 'unpublish':
                    $document = $this->documentManager->find($id, $locale);

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'live'
                    );

                    $this->documentManager->unpublish($document, $locale);
                    $this->documentManager->flush();

                    $data = $this->documentManager->find($id, $locale);
                    break;
                case 'remove-draft':
                    $webspace = $this->getWebspace($request);
                    $data = $this->documentManager->find($id, $locale);

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $data),
                        'live'
                    );

                    $this->documentManager->removeDraft($data, $locale);
                    $this->documentManager->flush();
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            $context = new Context();
            $context->setGroups(['defaultPage']);

            // prepare view
            $view = $this->view($data, null !== $data ? 200 : 204);

            $view->setContext($context);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    public function postAction(Request $request): Response
    {
        $type = 'page';
        $locale = $this->getLocale($request);
        $action = $request->get('action');

        $parentDocument = $this->documentManager->find(
            $this->getRequestParameter($request, 'parentId', true),
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->securityChecker->checkPermission(
            $this->getSecurityCondition($request, $parentDocument),
            'add'
        );

        $this->checkActionParameterSecurity($action, $request, $parentDocument);

        $document = $this->documentManager->create($type);
        $formType = $this->metadataFactory->getMetadataForAlias($type)->getFormType();

        $this->persistDocument($request, $formType, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    public function putAction(Request $request, string $id): Response
    {
        $locale = $this->getLocale($request);
        $action = $request->get('action');

        $document = $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->securityChecker->checkPermission(
            $this->getSecurityCondition($request, $document),
            'edit'
        );

        $this->checkActionParameterSecurity($action, $request, $document);

        $formType = $this->metadataFactory->getMetadataForClass(\get_class($document))->getFormType();

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($request, $formType, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    public function deleteAction(Request $request, string $id): Response
    {
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request);
        $force = $this->getBooleanRequestParameter($request, 'force', false, false);
        $deleteLocale = $this->getBooleanRequestParameter($request, 'deleteLocale', false, false);

        if (!$force) {
            $references = \array_filter(
                $this->nodeRepository->getReferences($id),
                function(PropertyInterface $reference) {
                    return $reference->getParent()->isNodeType('sulu:page');
                }
            );

            if (\count($references) > 0) {
                $items = [];

                foreach ($references as $reference) {
                    $content = $this->contentMapper->load(
                        $reference->getParent()->getIdentifier(),
                        $webspace,
                        $locale,
                        true
                    );

                    $items[] = [
                        'id' => $content->getUuid(),
                        'resourceKey' => PageDocument::RESOURCE_KEY,
                        'title' => $content->getTitle(),
                    ];
                }

                throw new ReferencingResourcesFoundException(
                    [
                        'id' => $id,
                        'resourceKey' => PageDocument::RESOURCE_KEY,
                    ],
                    $items,
                    \count($items)
                );
            }
        }

        $view = $this->responseDelete(
            $id,
            function($id) use ($request, $locale, $deleteLocale) {
                try {
                    $document = $this->documentManager->find($id);

                    $this->securityChecker->checkPermission(
                        $this->getSecurityCondition($request, $document),
                        'delete'
                    );

                    if ($deleteLocale) {
                        $this->documentManager->removeLocale($document, $locale);
                    } else {
                        $this->documentManager->remove($document);
                    }
                    $this->documentManager->flush();
                } catch (DocumentNotFoundException $ex) {
                    throw new EntityNotFoundException('Content', $id, $ex);
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns language code from request.
     *
     * @return string
     */
    public function getLocale(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', false, null);

        if ($locale) {
            return $locale;
        }

        return $this->getRequestParameter($request, 'language', true);
    }

    public function cgetAction(Request $request)
    {
        $ids = \preg_split('/[,]/', $request->get('ids'), -1, \PREG_SPLIT_NO_EMPTY);
        $parent = $request->get('parentId');
        $properties = \array_filter(\explode(',', $request->get('fields', 'title,published')));
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);
        $excludeShadows = $this->getBooleanRequestParameter($request, 'exclude-shadows', false, false);
        $expandedIds = $this->getRequestParameter(
            $request,
            'expandedIds',
            false,
            $this->getRequestParameter($request, 'selectedIds', false, null)
        );
        $locale = $this->getLocale($request);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', false);

        $webspaceNodes = null;
        if (!$parent) {
            $webspaceNodes = $webspaceKey ? static::WEBSPACE_NODE_SINGLE : static::WEBSPACE_NODES_ALL;
        }

        if (!$locale) {
            throw new MissingParameterException(\get_class($this), 'locale');
        }

        if (!$webspaceKey && !$webspaceNodes && !$parent) {
            throw new MissingParameterChoiceException(\get_class($this), ['webspace', 'webspace-nodes', 'parentId']);
        }

        if (!\in_array($webspaceNodes, [self::WEBSPACE_NODE_SINGLE, static::WEBSPACE_NODES_ALL, null])) {
            throw new ParameterDataTypeException(\get_class($this), 'webspace-nodes');
        }

        $contentRepository = $this->contentRepository;
        $user = $this->getUser();

        $mapping = MappingBuilder::create()
            ->setHydrateGhost(!$excludeGhosts)
            ->setHydrateShadow(!$excludeShadows)
            ->setResolveConcreteLocales(true)
            ->addProperties($properties)
            ->setResolveUrl(true)
            ->getMapping();

        try {
            if ($expandedIds) {
                return $this->getTreeContent($expandedIds, $locale, $webspaceKey, $webspaceNodes, $mapping, $user);
            }
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);

            return $this->handleView($view);
        }

        $contents = [];

        if ($ids) {
            $contents = $contentRepository->findByUuids($ids, $locale, $mapping, $user);
        } else {
            if ($parent) {
                $contents = $contentRepository->findByParentUuid($parent, $locale, $webspaceKey, $mapping, $user);
            } elseif ($webspaceKey) {
                $contents = $contentRepository->findByWebspaceRoot($locale, $webspaceKey, $mapping, $user);
            }

            if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
                $contents = $this->getWebspaceNodes($mapping, $contents, $locale, $user);
            } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
                $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
            }
        }

        $list = new CollectionRepresentation($contents, BasePageDocument::RESOURCE_KEY);
        $view = $this->view($list);

        return $this->handleView($view);
    }

    private function getTreeContent(
        string $id,
        string $locale,
        ?string $webspaceKey,
        ?string $webspaceNodes,
        MappingInterface $mapping,
        UserInterface $user
    ): Response {
        if (!\in_array($webspaceNodes, [static::WEBSPACE_NODE_SINGLE, static::WEBSPACE_NODES_ALL, null])) {
            throw new ParameterDataTypeException(\get_class($this), 'webspace-nodes');
        }

        try {
            $contents = $this->contentRepository->findParentsWithSiblingsByUuid(
                $id,
                $locale,
                $webspaceKey,
                $mapping,
                $user
            );
        } catch (ItemNotFoundException $e) {
            throw new EntityNotFoundException('node', $id, $e);
        }

        if ($webspaceNodes === static::WEBSPACE_NODES_ALL) {
            $contents = $this->getWebspaceNodes($mapping, $contents, $locale, $user);
        } elseif ($webspaceNodes === static::WEBSPACE_NODE_SINGLE) {
            $contents = $this->getWebspaceNode($mapping, $contents, $webspaceKey, $locale, $user);
        }

        $view = $this->view(new CollectionRepresentation($contents, BasePageDocument::RESOURCE_KEY));

        return $this->handleView($view);
    }

    private function persistDocument(Request $request, $formType, $document, $locale): void
    {
        $data = $request->request->all();

        if ($request->query->has('parentId')) {
            $data['parent'] = $request->query->get('parentId');
        }

        $form = $this->formFactory->create(
            $formType,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
                'webspace_key' => $this->getWebspace($request),
            ]
        );
        $form->submit($data, false);

        if (\array_key_exists('author', $data) && null === $data['author']) {
            $document->setAuthor(null);
        }

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist(
            $document,
            $locale,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
            ]
        );
    }

    private function handleActionParameter(?string $actionParameter, $document, string $locale)
    {
        switch ($actionParameter) {
            case 'publish':
                $this->documentManager->publish($document, $locale);
                break;
        }
    }

    private function checkActionParameterSecurity(?string $actionParameter, Request $request, $document = null): void
    {
        $permission = null;
        switch ($actionParameter) {
            case 'publish':
                $permission = 'live';
                break;
        }

        if (!$permission) {
            return;
        }

        $this->securityChecker->checkPermission(
            $this->getSecurityCondition($request, $document),
            $permission
        );
    }

    /**
     * @return Content[]
     */
    private function getWebspaceNodes(
        MappingInterface $mapping,
        array $contents,
        string $locale,
        UserInterface $user
    ) {
        $paths = [];
        $webspaces = [];
        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            if (null === $webspace->getLocalization($locale) || false === $this->securityChecker->hasPermission(
                new SecurityCondition(PageAdmin::getPageSecurityContext($webspace->getKey()), $locale, SecurityBehavior::class),
                'view'
            )) {
                continue;
            }
            $paths[] = $this->sessionManager->getContentPath($webspace->getKey());
            $webspaces[$webspace->getKey()] = $webspace;
        }

        return $this->getWebspaceNodesByPaths($paths, $locale, $mapping, $webspaces, $contents, $user);
    }

    /**
     * @return Content[]
     */
    private function getWebspaceNode(
        MappingInterface $mapping,
        array $contents,
        string $webspaceKey,
        string $locale,
        UserInterface $user
    ) {
        /** @var Webspace $webspace */
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        if (null === $webspace->getLocalization($locale) || false === $this->securityChecker->hasPermission(
            new SecurityCondition(PageAdmin::getPageSecurityContext($webspace->getKey()), $locale, SecurityBehavior::class),
            'view'
        )) {
            return [];
        }

        $paths = [$this->sessionManager->getContentPath($webspace->getKey())];
        $webspaces = [$webspace->getKey() => $webspace];

        return $this->getWebspaceNodesByPaths(
            $paths,
            $locale,
            $mapping,
            $webspaces,
            $contents,
            $user
        );
    }

    /**
     * @param string[] $paths
     * @param Webspace[] $webspaces
     * @param Content[] $contents
     *
     * @return Content[]
     */
    private function getWebspaceNodesByPaths(
        array $paths,
        string $locale,
        MappingInterface $mapping,
        array $webspaces,
        array $contents,
        UserInterface $user
    ) {
        $webspaceKey = null;
        if ($firstContent = \reset($contents)) {
            $webspaceKey = $firstContent->getWebspaceKey();
        }

        $webspaceContents = $this->contentRepository->findByPaths(
            $paths,
            $locale,
            $mapping,
            $user
        );

        foreach ($webspaceContents as $webspaceContent) {
            $webspaceContent->setDataProperty('title', $webspaces[$webspaceContent->getWebspaceKey()]->getName());

            if ($webspaceContent->getWebspaceKey() === $webspaceKey) {
                $webspaceContent->setChildren($contents);
            }
        }

        return $webspaceContents;
    }

    private function getWebspace(Request $request, bool $force = true): ?string
    {
        return $this->getRequestParameter($request, 'webspace', $force);
    }

    private function getSecurityCondition(Request $request, $document = null)
    {
        return new SecurityCondition(
            PageAdmin::getPageSecurityContext($document->getWebspaceName()),
            $this->getLocale($request),
            SecurityBehavior::class,
            $request->get('id')
        );
    }
}
