<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use ONGR\ElasticsearchBundle\Mapping\Caser;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhrasePrefixQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactoryInterface;
use Sulu\Bundle\ArticleBundle\ListBuilder\ElasticSearchFieldDescriptor;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Hash\RequestHashCheckerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides API for articles.
 */
class ArticleController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use ArticleViewDocumentIdTrait;
    use RequestParametersTrait;
    public const DOCUMENT_TYPE = 'article';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var ListRestHelperInterface
     */
    private $restHelper;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RequestHashCheckerInterface
     */
    private $requestHashChecker;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var bool
     */
    private $displayTabAll;

    /**
     * @var DocumentInspector|null
     */
    private $documentInspector;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        DocumentManagerInterface $documentManager,
        ContentMapperInterface $contentMapper,
        MetadataFactoryInterface $metadataFactory,
        ListRestHelperInterface $restHelper,
        Manager $manager,
        DocumentFactoryInterface $documentFactory,
        FormFactoryInterface $formFactory,
        RequestHashCheckerInterface $requestHashChecker,
        SecurityCheckerInterface $securityChecker,
        bool $displayTabAll,
        ?TokenStorageInterface $tokenStorage = null,
        ?DocumentInspector $documentInspector = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->documentManager = $documentManager;
        $this->contentMapper = $contentMapper;
        $this->metadataFactory = $metadataFactory;
        $this->restHelper = $restHelper;
        $this->manager = $manager;
        $this->documentFactory = $documentFactory;
        $this->formFactory = $formFactory;
        $this->requestHashChecker = $requestHashChecker;
        $this->securityChecker = $securityChecker;
        $this->displayTabAll = $displayTabAll;
        $this->documentInspector = $documentInspector;

        if (null === $this->documentInspector) {
            @trigger_deprecation('sulu/article-bundle', '2.5', 'Instantiating the ArticleController without the $documentInspector argument is deprecated!');
        }
    }

    /**
     * Create field-descriptor array.
     *
     * @return ElasticSearchFieldDescriptor[]
     */
    protected function getFieldDescriptors(): array
    {
        return [
            'uuid' => ElasticSearchFieldDescriptor::create('id', 'public.id')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'typeTranslation' => ElasticSearchFieldDescriptor::create('typeTranslation', 'sulu_article.list.type')
                ->setSortField('typeTranslation.raw')
                ->setVisibility(
                    $this->displayTabAll ?
                        FieldDescriptorInterface::VISIBILITY_YES :
                        FieldDescriptorInterface::VISIBILITY_NEVER
                )
                ->build(),
            'title' => ElasticSearchFieldDescriptor::create('title', 'public.title')
                ->setSortField('title.raw')
                ->setSearchField('title')
                ->setSearchability(FieldDescriptor::SEARCHABILITY_YES)
                ->build(),
            'creatorFullName' => ElasticSearchFieldDescriptor::create('creatorFullName', 'sulu_article.list.creator')
                ->setSortField('creatorFullName.raw')
                ->build(),
            'changerFullName' => ElasticSearchFieldDescriptor::create('changerFullName', 'sulu_article.list.changer')
                ->setSortField('changerFullName.raw')
                ->build(),
            'authorFullName' => ElasticSearchFieldDescriptor::create('authorFullName', 'sulu_article.author')
                ->setSortField('authorFullName.raw')
                ->build(),
            'created' => ElasticSearchFieldDescriptor::create('created', 'public.created')
                ->setSortField('created')
                ->setType('datetime')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'changed' => ElasticSearchFieldDescriptor::create('changed', 'public.changed')
                ->setSortField('changed')
                ->setType('datetime')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'authored' => ElasticSearchFieldDescriptor::create('authored', 'sulu_article.authored')
                ->setSortField('authored')
                ->setType('datetime')
                ->build(),
            'localizationState' => ElasticSearchFieldDescriptor::create('localizationState')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'published' => ElasticSearchFieldDescriptor::create('published')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'publishedState' => ElasticSearchFieldDescriptor::create('publishedState')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'routePath' => ElasticSearchFieldDescriptor::create('routePath')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->setSearchField('route_path.value')
                ->setSearchability(FieldDescriptor::SEARCHABILITY_YES)
                ->build(),
        ];
    }

    /**
     * Returns list of articles.
     */
    public function cgetAction(Request $request): Response
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch();

        $limit = (int) $this->restHelper->getLimit();
        $page = (int) $this->restHelper->getPage();

        /** @var array{
         *     authored?: array{
         *         from: string,
         *         to: string,
         *     },
         *     type?: string,
         *     contactId?: string,
         *     categoryId?: string,
         *     tagId?: string,
         *     pageId?: string,
         *     publishedState?: string,
         *  } $filter */
        $filter = $request->query->all()['filter'] ?? [];

        if (null !== $locale) {
            $search->addQuery(new TermQuery('locale', $locale));
        }

        if (\count($ids = \array_filter(\explode(',', $request->get('ids', ''))))) {
            $search->addQuery(new IdsQuery($this->getViewDocumentIds($ids, $locale)));
            $limit = \count($ids);
        }

        $fieldDescriptors = $this->getFieldDescriptors();

        $searchFields = $this->restHelper->getSearchFields();
        if (0 === \count($searchFields)) {
            foreach ($fieldDescriptors as $fieldDescriptor) {
                if (FieldDescriptorInterface::SEARCHABILITY_YES !== $fieldDescriptor->getSearchability()) {
                    continue;
                }

                $searchFields[] = $fieldDescriptor->getSearchField();
            }
        }

        $searchPattern = $this->restHelper->getSearchPattern();
        if (!empty($searchPattern)) {
            $boolQuery = new BoolQuery();
            foreach ($searchFields as $searchField) {
                $boolQuery->add(new MatchPhrasePrefixQuery($searchField, $searchPattern), BoolQuery::SHOULD);
            }
            $search->addQuery($boolQuery);
        }

        if (null !== ($typeString = $request->get('types', $filter['type'] ?? null))) {
            $types = \explode(',', $typeString);

            if (\count($types) > 1) {
                $query = new BoolQuery();

                foreach ($types as $type) {
                    $query->add(new TermQuery('type', $type), BoolQuery::SHOULD);
                }

                $search->addQuery($query);
            } elseif ($types[0]) {
                $search->addQuery(new TermQuery('type', $types[0]));
            }
        }

        if ($contactId = $request->get('contactId', $filter['contactId'] ?? null)) {
            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery('changer_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('creator_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('author_id', $contactId), BoolQuery::SHOULD);
            $search->addQuery($boolQuery);
        }

        if ($categoryId = $request->get('categoryId', $filter['categoryId'] ?? null)) {
            $search->addQuery(new TermQuery('excerpt.categories.id', $categoryId), BoolQuery::MUST);
        }

        if ($tagId = $request->get('tagId', $filter['tagId'] ?? null)) {
            $search->addQuery(new TermQuery('excerpt.tags.id', $tagId), BoolQuery::MUST);
        }

        if ($pageId = $request->get('pageId', $filter['pageId'] ?? null)) {
            $search->addQuery(new TermQuery('parent_page_uuid', $pageId), BoolQuery::MUST);
        }

        if ($workflowStage = $request->get('workflowStage', $filter['publishedState'] ?? null)) {
            $search->addQuery(new TermQuery('published_state', 'published' === $workflowStage), BoolQuery::MUST);
        }

        if ($this->getBooleanRequestParameter($request, 'exclude-shadows', false, false)) {
            $search->addQuery(new TermQuery('localization_state.state', 'shadow'), BoolQuery::MUST_NOT);
        }

        if ($this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false)) {
            $search->addQuery(new TermQuery('localization_state.state', 'ghost'), BoolQuery::MUST_NOT);
        }

        $authoredFrom = $request->get('authoredFrom', $this->convertDateTime($filter['authored']['from'] ?? null));
        $authoredTo = $request->get('authoredTo', $this->convertDateTime($filter['authored']['to'] ?? null));
        if ($authoredFrom || $authoredTo) {
            $search->addQuery($this->getRangeQuery('authored', $authoredFrom, $authoredTo), BoolQuery::MUST);
        }

        if (null === $search->getQueries()) {
            $search->addQuery(new MatchAllQuery());
        }

        if (null !== $this->restHelper->getSortColumn()
            && $sortField = $this->getSortFieldName($this->restHelper->getSortColumn())
        ) {
            $search->addSort(
                new FieldSort($sortField, $this->restHelper->getSortOrder())
            );
        } else {
            $search->addSort(
                new FieldSort('created', 'ASC')
            );
        }

        if ($limit) {
            $search->setSize($limit);
            $search->setFrom(($page - 1) * $limit);

            $fields = \array_merge(
                $this->restHelper->getFields() ?: [],
                ['id', 'localizationState', 'publishedState', 'published', 'title', 'routePath']
            );
            $fieldDescriptors = \array_filter(
                $fieldDescriptors,
                function(FieldDescriptorInterface $fieldDescriptor) use ($fields) {
                    return \in_array($fieldDescriptor->getName(), $fields);
                }
            );
        } else {
            $search->setSize(1000);
            $search->setScroll('1m');
        }

        if (\method_exists($search, 'setTrackTotalHits')) {
            $search->setTrackTotalHits(true);
        }

        $searchResult = $repository->findRaw($search);
        $result = [];
        foreach ($searchResult as $document) {
            $documentData = $this->normalize($document['_source'], $fieldDescriptors);
            $documentData['ghostLocale'] = 'ghost' == $documentData['localizationState']['state'] ? $documentData['localizationState']['locale'] : null;

            if (false !== ($index = \array_search($documentData['id'], $ids))) {
                $result[$index] = $documentData;
            } else {
                $result[] = $documentData;
            }
        }

        if (\count($ids)) {
            \ksort($result);
            $result = \array_values($result);
        }

        $count = $searchResult->count();

        return $this->handleView(
            $this->view(
                new ListRepresentation(
                    $result,
                    'articles',
                    'sulu_article.get_articles',
                    $request->query->all(),
                    $page,
                    $limit,
                    $count
                )
            )
        );
    }

    /**
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    private function normalize(array $document, array $fieldDescriptors)
    {
        $result = [];
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $property = Caser::snake($fieldDescriptor->getName());
            if ('id' === $property) {
                $property = 'uuid';
            }

            $result[$fieldDescriptor->getName()] = \array_key_exists($property, $document) ? $document[$property] : null;
        }

        return $result;
    }

    /**
     * Returns query to filter by given range.
     */
    private function getRangeQuery(string $field, string $from, string $to): RangeQuery
    {
        return new RangeQuery($field, \array_filter(['gte' => $from, 'lte' => $to]));
    }

    /**
     * Returns single article.
     *
     * @Get(defaults={"id" = ""})
     */
    public function getAction(Request $request, string $id): Response
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        /** @var ArticleDocument $document */
        $document = $this->documentManager->find(
            $id,
            $locale
        );

        if ($this->documentInspector) {
            $localizationState = $this->documentInspector->getLocalizationState($document);

            if (LocalizationState::GHOST === $localizationState) {
                $document = $this->documentManager->find(
                    $id,
                    $locale,
                    [
                        'load_ghost_content' => false,
                        'structure_type' => $document->getStructureType(),
                    ]
                );
            }
        }

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Create article.
     */
    public function postAction(Request $request): Response
    {
        $action = $request->get('action');
        $document = $this->documentManager->create(self::DOCUMENT_TYPE);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = $request->request->all();

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Update articles.
     */
    public function putAction(Request $request, string $id): Response
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $action = $request->get('action');
        $data = $request->request->all();

        $document = $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Deletes multiple documents.
     */
    public function cdeleteAction(Request $request): Response
    {
        $ids = \array_filter(\explode(',', $request->get('ids', '')));

        $documentManager = $this->documentManager;
        foreach ($ids as $id) {
            $document = $documentManager->find($id);
            $documentManager->remove($document);
            $documentManager->flush();
        }

        return $this->handleView($this->view(null));
    }

    /**
     * Deletes multiple documents.
     */
    public function deleteAction(Request $request, string $id): Response
    {
        $locale = $this->getLocale($request);
        $deleteLocale = $this->getBooleanRequestParameter($request, 'deleteLocale', false, false);

        $document = $this->documentManager->find($id);
        if ($deleteLocale) {
            $this->documentManager->removeLocale($document, $locale);
        } else {
            $this->documentManager->remove($document);
        }
        $this->documentManager->flush();

        return $this->handleView($this->view(null));
    }

    /**
     * Trigger a action for given article specified over get-action parameter.
     *
     * @Post("/articles/{id}")
     */
    public function postTriggerAction(string $id, Request $request): Response
    {
        // extract parameter
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getRequestParameter($request, 'locale', false);

        // prepare vars
        $view = null;
        $data = null;
        $userId = $this->getUser()->getId();

        try {
            switch ($action) {
                case 'unpublish':
                    $document = $this->documentManager->find($id, $locale);
                    $this->documentManager->unpublish($document, $locale);
                    $this->documentManager->flush();

                    $data = $this->documentManager->find($id, $locale);

                    break;
                case 'remove-draft':
                    $data = $this->documentManager->find($id, $locale);
                    $this->documentManager->removeDraft($data, $locale);
                    $this->documentManager->flush();

                    break;
                case 'copy-locale':
                    $srcLocale = $this->getRequestParameter($request, 'src', false, $locale);
                    $destLocales = $this->getRequestParameter($request, 'dest', true);
                    $destLocales = \explode(',', $destLocales);

                    foreach ($destLocales as $destLocale) {
                        $this->securityChecker->checkPermission(
                            new SecurityCondition($this->getSecurityContext(), $destLocale),
                            PermissionTypes::EDIT
                        );
                    }

                    $document = $this->documentManager->find($id, $srcLocale);
                    foreach ($destLocales as $destLocale) {
                        $this->documentManager->copyLocale($document, $srcLocale, $destLocale);
                    }

                    $this->documentManager->flush();

                    $data = $this->documentManager->find($id, $locale);

                    break;
                case 'copy':
                    /** @var ArticleDocument $document */
                    $document = $this->documentManager->find($id, $locale);
                    /** @var string $copiedPath */
                    $copiedPath = $this->documentManager->copy($document, \dirname($document->getPath()));
                    $this->documentManager->flush();

                    $data = $this->documentManager->find($copiedPath, $locale);

                    break;
                case 'order':
                    $this->orderPages($this->getRequestParameter($request, 'pages', true), $locale);
                    $this->documentManager->flush();
                    $this->documentManager->clear();

                    $data = $this->documentManager->find($id, $locale);

                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // create context
            $context = new Context();
            $context->setSerializeNull(true);
            $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

            // prepare view
            $view = $this->view($data);
            $view->setContext($context);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Ordering given pages.
     */
    private function orderPages(array $pages, string $locale): void
    {
        $documentManager = $this->documentManager;

        for ($i = 0; $i < \count($pages); ++$i) {
            $document = $documentManager->find($pages[$i], $locale);
            $documentManager->reorder($document, null);
        }
    }

    public function getSecurityContext()
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }

    /**
     * Persists the document using the given Formation.
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     */
    private function persistDocument(array $data, object $document, string $locale): void
    {
        /** @var class-string<\Symfony\Component\Form\FormTypeInterface> $formType */
        $formType = $this->metadataFactory->getMetadataForAlias('article')->getFormType();
        $form = $this->formFactory->create(
            $formType,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
            ]
        );
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        if (\array_key_exists('author', $data) && null === $data['author']) {
            $document->setAuthor(null);
        }

        if (\array_key_exists('additionalWebspaces', $data) && null === $data['additionalWebspaces']) {
            $document->setAdditionalWebspaces(null);
        }

        if (\array_key_exists('customizeWebspaceSettings', $data) && false === $data['customizeWebspaceSettings']) {
            $document->setMainWebspace(null);
            $document->setAdditionalWebspaces(null);
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

    /**
     * Delegates actions by given actionParameter, which can be retrieved from the request.
     */
    private function handleActionParameter(?string $actionParameter, object $document, string $locale): void
    {
        switch ($actionParameter) {
            case 'publish':
                $this->documentManager->publish($document, $locale);

                break;
        }
    }

    private function getSortFieldName(string $sortBy): ?string
    {
        $sortBy = Caser::snake($sortBy);
        $fieldDescriptors = $this->getFieldDescriptors();

        if (\array_key_exists($sortBy, $fieldDescriptors)) {
            return $fieldDescriptors[$sortBy]->getSortField();
        }

        return null;
    }

    private function convertDateTime(?string $dateTimeString): ?string
    {
        if (!$dateTimeString) {
            return null;
        }

        $dateTime = new \DateTime($dateTimeString);

        return $dateTime->format('Y-m-d');
    }
}
