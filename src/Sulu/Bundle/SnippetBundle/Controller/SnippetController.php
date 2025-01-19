<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use PHPCR\NodeInterface;
use Sulu\Bundle\CoreBundle\Serializer\Exclusion\FieldsExclusionStrategy;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Hash\RequestHashChecker;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * handles snippets.
 */
class SnippetController implements SecuredControllerInterface, ClassResourceInterface
{
    use RequestParametersTrait;

    public function __construct(
        private ViewHandler $viewHandler,
        private ContentMapper $contentMapper,
        private StructureManagerInterface $structureManager,
        private SnippetRepository $snippetRepository,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private DefaultSnippetManagerInterface $defaultSnippetManager,
        private DocumentManagerInterface $documentManager,
        private FormFactory $formFactory,
        private RequestHashChecker $requestHashChecker,
        private ListRestHelper $listRestHelper,
        private MetadataFactoryInterface $metadataFactory,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Returns list of snippets.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getLocale($request);

        // if the type parameter is falsy, assign NULL to $type
        $types = null;

        if ($request->query->has('areas')) {
            $types = \array_map(function($area) {
                return $this->defaultSnippetManager->getTypeForArea($area);
            }, \explode(',', $request->query->get('areas')));
        } elseif ($request->query->has('types')) {
            $types = \explode(',', $request->query->get('types'));
        }

        if ($types && \count($types) > 1) {
            // TODO Implement filtering by multiple types
            throw new \Exception('Filtering by multiple types or areas at once is currently not supported!');
        }

        $idsString = $request->get('ids');

        if ($idsString) {
            $ids = \explode(',', $idsString);
            $snippets = $this->snippetRepository->getSnippetsByUuids($ids, $locale, true);
            $total = \count($snippets);
        } else {
            $snippets = $this->snippetRepository->getSnippets(
                $locale,
                $types ? $types[0] : null,
                $this->listRestHelper->getOffset(),
                $this->listRestHelper->getLimit(),
                $this->listRestHelper->getSearchPattern(),
                $this->listRestHelper->getSortColumn(),
                $this->listRestHelper->getSortOrder()
            );

            $total = $this->snippetRepository->getSnippetsAmount(
                $locale,
                $types ? $types[0] : null,
                $this->listRestHelper->getSearchPattern(),
                $this->listRestHelper->getSortColumn(),
                $this->listRestHelper->getSortOrder()
            );
        }

        $data = new PaginatedRepresentation(
            $snippets,
            SnippetDocument::RESOURCE_KEY,
            (int) $this->listRestHelper->getPage(),
            (int) $this->listRestHelper->getLimit(),
            (int) $total
        );

        $view = View::create($data);

        $requestedFields = $this->listRestHelper->getFields() ?? [];
        if ([] !== $requestedFields) {
            $context = new Context();
            $context->addExclusionStrategy(new FieldsExclusionStrategy($requestedFields));
            $view->setContext($context);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns snippet by ID.
     *
     * @param string $id
     *
     * @return Response
     */
    public function getAction(Request $request, $id = null)
    {
        $locale = $this->getLocale($request);

        $snippet = $this->findDocument($id, $locale);
        $view = View::create($snippet);

        return $this->viewHandler->handle($view);
    }

    /**
     * Saves a new snippet.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $document = $this->documentManager->create(Structure::TYPE_SNIPPET);
        $form = $this->processForm($request, $document);

        return $this->handleView($form->getData());
    }

    /**
     * Saves an existing snippet.
     *
     * @param string $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        $document = $this->findDocument($id, $this->getLocale($request));

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());
        if (!$this->checkAreaSnippet($request, $document)) {
            return new JsonResponse(
                [
                    'structures' => [],
                    'other' => [],
                    'isDefault' => true,
                ],
                409
            );
        }

        $this->processForm($request, $document);

        return $this->handleView($document);
    }

    /**
     * Deletes an existing Snippet.
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspaceKey = (string) $request->query->get('webspace');

        $references = $this->snippetRepository->getReferences($id);

        if (\count($references) > 0) {
            $force = $request->query->get('force', false);
            if ($force) {
                $this->contentMapper->delete($id, $webspaceKey, true);
            } else {
                $this->throwReferentialIntegrityException($webspaceKey, $references, $id, $locale);
            }
        } else {
            $this->contentMapper->delete($id, $webspaceKey);
        }

        return new JsonResponse();
    }

    /**
     * trigger a action for given snippet specified over get-action parameter.
     *
     * @param string $id
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $view = null;
        $snippet = null;

        $locale = $this->getLocale($request);
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'copy-locale':
                    $srcLocale = $this->getRequestParameter($request, 'src', false, $locale);
                    $destLocales = \explode(',', $this->getRequestParameter($request, 'dest', true));

                    $document = $this->documentManager->find($id, $srcLocale);
                    foreach ($destLocales as $destLocale) {
                        $this->documentManager->copyLocale($document, $srcLocale, $destLocale);
                    }

                    // publish the snippet in every dest locale, otherwise it's not in the live workspace.
                    foreach ($destLocales as $destLocale) {
                        $destSnippet = $this->findDocument($id, $destLocale);
                        $this->documentManager->publish($destSnippet, $destLocale);
                    }

                    // flush all published snippets
                    $this->documentManager->flush();

                    if ($srcLocale !== $locale) {
                        return $this->handleView($this->findDocument($id, $locale));
                    }

                    break;
                case 'copy':
                    /** @var SnippetDocument $document */
                    $document = $this->documentManager->find($id, $locale);
                    $copiedPath = $this->documentManager->copy($document, \dirname($document->getPath()));
                    $this->documentManager->flush();

                    $this->documentManager->publish($document, $locale);
                    $this->documentManager->flush();

                    $id = $copiedPath;
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            $view = View::create($this->findDocument($id, $locale));
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns user.
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \InvalidArgumentException('No user is set');
        }

        return $token->getUser();
    }

    /**
     * Decorate snippet for HATEOAS.
     */
    private function decorateSnippet(array $snippet, $locale)
    {
        return \array_merge(
            $snippet,
            [
                '_links' => [
                    'self' => $this->urlGenerator->generate(
                        'sulu_snippet.get_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                    'delete' => $this->urlGenerator->generate(
                        'sulu_snippet.delete_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                    'new' => $this->urlGenerator->generate('sulu_snippet.post_snippet', ['language' => $locale]),
                    'update' => $this->urlGenerator->generate(
                        'sulu_snippet.put_snippet',
                        ['id' => $snippet['id'], 'language' => $locale]
                    ),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getLocale(Request $request)
    {
        if ($request->query->has('locale')) {
            return $request->query->get('locale');
        }

        if ($request->query->has('language')) {
            @trigger_deprecation('sulu/sulu', '2.1', 'The usage of the "language" parameter in the SnippetController is deprecated. Please use "locale" instead.');
        }

        return $request->query->get('language', null);
    }

    public function getSecurityContext()
    {
        return 'sulu.global.snippets';
    }

    /**
     * Return a response for the case where there is an referential integrity violation.
     *
     * It will return a 409 (Conflict) response with an array of structures which reference
     * the node and an array of "other" nodes (i.e. non-structures) which reference the node.
     *
     * @param string $webspace
     * @param NodeInterface[] $references
     * @param string $id
     * @param string $locale
     *
     * @throws ReferencingResourcesFoundException
     */
    private function throwReferentialIntegrityException($webspace, $references, $id, $locale): void
    {
        $items = [];

        foreach ($references as $reference) {
            $parentReference = $reference->getParent();
            if ($parentReference->isNodeType('sulu:page') || $parentReference->isNodeType('sulu:home')) {
                $content = $this->contentMapper->load(
                    $parentReference->getIdentifier(),
                    $webspace,
                    $locale,
                    true
                );
                $items[] = [
                    'id' => $content->getUuid(),
                    'resourceKey' => PageDocument::RESOURCE_KEY,
                    'title' => $content->getPropertyValue('title'),
                ];
            }
        }

        foreach ($this->defaultSnippetManager->loadWebspaces($id) as $defaultSnippetWebspace) {
            $items[] = [
                'id' => $defaultSnippetWebspace->getKey(),
                'resourceKey' => 'webspaces',
                'title' => $this->translator->trans(
                    'sulu_snippet.webspace_default_snippet',
                    ['{webspaceKey}' => $defaultSnippetWebspace->getName()],
                    'admin'
                ),
            ];
        }

        throw new ReferencingResourcesFoundException(
            [
                'id' => $id,
                'resourceKey' => SnippetDocument::RESOURCE_KEY,
            ],
            $items,
            \count($items)
        );
    }

    private function findDocument($id, $locale)
    {
        return $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );
    }

    private function processForm(Request $request, $document)
    {
        $locale = $this->getLocale($request);
        $data = $request->request->all();
        $data['workflowStage'] = $request->get('state', WorkflowStage::PUBLISHED);

        /** @var class-string<FormTypeInterface> $formType */
        $formType = $this->metadataFactory->getMetadataForAlias(Structure::TYPE_SNIPPET)->getFormType();
        $form = $this->formFactory->create($formType, $document, [
            'csrf_protection' => false,
        ]);
        $form->submit($data, false);

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
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        return $form;
    }

    private function handleView($document)
    {
        $view = View::create($document);

        return $this->viewHandler->handle($view);
    }

    private function checkAreaSnippet(Request $request, SnippetDocument $document)
    {
        $force = $request->headers->get('SuluForcePut', false);
        $structureType = $request->request->get('template');

        return $force
            || $structureType === $document->getStructureType()
            || !$this->defaultSnippetManager->isDefault($document->getUuid())
            || $structureType === $this->defaultSnippetManager->loadType($document->getUuid());
    }
}
