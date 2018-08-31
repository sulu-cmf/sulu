<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use PHPCR\NodeInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\SmartContent\Exception\PageOutOfBoundsException;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Content type for smart selection.
 */
class ContentType extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Contains cached values.
     *
     * @var array
     */
    private $cache = [];

    /**
     * @var TagRequestHandlerInterface
     */
    private $tagRequestHandler;

    /**
     * @var CategoryRequestHandlerInterface
     */
    private $categoryRequestHandler;

    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $tagReferenceStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $categoryReferenceStore;

    /**
     * SmartContentType constructor.
     *
     * @param DataProviderPoolInterface $dataProviderPool
     * @param TagManagerInterface $tagManager
     * @param RequestStack $requestStack
     * @param TagRequestHandlerInterface $tagRequestHandler
     * @param CategoryRequestHandlerInterface $categoryRequestHandler
     * @param ReferenceStoreInterface $tagReferenceStore
     * @param ReferenceStoreInterface $categoryReferenceStore
     * @param TargetGroupStoreInterface $targetGroupStore
     */
    public function __construct(
        DataProviderPoolInterface $dataProviderPool,
        TagManagerInterface $tagManager,
        RequestStack $requestStack,
        TagRequestHandlerInterface $tagRequestHandler,
        CategoryRequestHandlerInterface $categoryRequestHandler,
        ReferenceStoreInterface $tagReferenceStore,
        ReferenceStoreInterface $categoryReferenceStore,
        TargetGroupStoreInterface $targetGroupStore = null
    ) {
        $this->dataProviderPool = $dataProviderPool;
        $this->tagManager = $tagManager;
        $this->requestStack = $requestStack;
        $this->tagRequestHandler = $tagRequestHandler;
        $this->categoryRequestHandler = $categoryRequestHandler;
        $this->tagReferenceStore = $tagReferenceStore;
        $this->categoryReferenceStore = $categoryReferenceStore;
        $this->targetGroupStore = $targetGroupStore;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = $node->getPropertyValueWithDefault($property->getName(), '{}');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!empty($data['tags'])) {
            $data['tags'] = $this->tagManager->resolveTagIds($data['tags']);
        }

        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        $this->resolveTags($value, 'tags');
        $this->resolveTags($value, 'websiteTags');

        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        $provider = $this->getProvider($property);
        $configuration = $provider->getConfiguration();

        $defaults = [
            'provider' => new PropertyParameter('provider', 'pages'),
            'alias' => null,
            'page_parameter' => new PropertyParameter('page_parameter', 'p'),
            'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
            'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
            'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
            'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
            'sorting' => new PropertyParameter('sorting', $configuration->getSorting(), 'collection'),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
            'category_root' => new PropertyParameter('category_root', null),
            'display_options' => new PropertyParameter(
                'display_options',
                [
                    'tags' => new PropertyParameter('tags', true),
                    'categories' => new PropertyParameter('categories', true),
                    'sorting' => new PropertyParameter('sorting', true),
                    'limit' => new PropertyParameter('limit', true),
                    'presentAs' => new PropertyParameter('presentAs', true),
                ],
                'collection'
            ),
            'has' => [
                'datasource' => $configuration->hasDatasource(),
                'tags' => $configuration->hasTags(),
                'categories' => $configuration->hasCategories(),
                'sorting' => $configuration->hasSorting(),
                'limit' => $configuration->hasLimit(),
                'presentAs' => $configuration->hasPresentAs(),
                'audienceTargeting' => $configuration->hasAudienceTargeting(),
            ],
            'datasourceResourceKey' => $configuration->getDatasourceResourceKey(),
            'datasourceAdapter' => $configuration->getDatasourceAdapter(),
            'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
        ];

        if ($provider instanceof DataProviderAliasInterface) {
            $defaults['alias'] = $provider->getAlias();
        }

        return array_merge(
            parent::getDefaultParams(),
            $defaults,
            $provider->getDefaultPropertyParameter()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        // check memoize
        $hash = spl_object_hash($property);
        if (array_key_exists($hash, $this->cache)) {
            return $this->cache[$hash];
        }

        /** @var PropertyParameter[] $params */
        $params = array_merge(
            $this->getDefaultParams($property),
            $property->getParams()
        );

        // prepare filters
        $filters = $property->getValue();
        $filters['excluded'] = [$property->getStructure()->getUuid()];

        // default value of tags/category is an empty array
        if (!array_key_exists('tags', $filters)) {
            $filters['tags'] = [];
        }
        if (!array_key_exists('categories', $filters)) {
            $filters['categories'] = [];
        }

        // extends selected filter with requested tags
        $filters['websiteTags'] = $this->tagRequestHandler->getTags($params['tags_parameter']->getValue());
        $filters['websiteTagsOperator'] = $params['website_tags_operator']->getValue();

        // extends selected filter with requested categories
        $filters['websiteCategories'] = $this->categoryRequestHandler->getCategories(
            $params['categories_parameter']->getValue()
        );
        $filters['websiteCategoriesOperator'] = $params['website_categories_operator']->getValue();

        if ($this->targetGroupStore && isset($filters['audienceTargeting']) && $filters['audienceTargeting']) {
            $filters['targetGroupId'] = $this->targetGroupStore->getTargetGroupId();
        }

        // resolve tags to id
        $this->resolveTags($filters, 'tags');

        // resolve website tags to id
        $this->resolveTags($filters, 'websiteTags');

        foreach (array_merge($filters['tags'], $filters['websiteTags']) as $item) {
            $this->tagReferenceStore->add($item);
        }

        foreach (array_merge($filters['categories'], $filters['websiteCategories']) as $item) {
            $this->categoryReferenceStore->add($item);
        }

        // get provider
        $provider = $this->getProvider($property);
        $configuration = $provider->getConfiguration();

        // prepare pagination, limitation and options
        $page = 1;
        $limit = (array_key_exists('limitResult', $filters) && $configuration->hasLimit()) ?
            $filters['limitResult'] : null;
        $options = [
            'webspaceKey' => $property->getStructure()->getWebspaceKey(),
            'locale' => $property->getStructure()->getLanguageCode(),
        ];

        if (isset($params['max_per_page']) && $configuration->hasPagination()) {
            // is paginated
            $page = $this->getCurrentPage($params['page_parameter']->getValue());
            $pageSize = intval($params['max_per_page']->getValue());

            // resolve paginated filters
            $data = $provider->resolveResourceItems(
                $filters,
                $params,
                $options,
                (!empty($limit) ? intval($limit) : null),
                $page,
                $pageSize
            );

            if ($page > 1 && 0 === count($data->getItems())) {
                throw new PageOutOfBoundsException($page);
            }
        } else {
            $data = $provider->resolveResourceItems(
                $filters,
                $params,
                $options,
                (!empty($limit) ? intval($limit) : null)
            );
        }

        // append view data
        $filters['page'] = $page;
        $filters['hasNextPage'] = $data->getHasNextPage();
        $filters['paginated'] = $configuration->hasPagination();
        $property->setValue($filters);

        // save result in cache
        return $this->cache[$hash] = $data->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        /** @var PropertyParameter[] $params */
        $params = array_merge(
            $this->getDefaultParams($property),
            $property->getParams()
        );

        $this->getContentData($property);
        $config = $property->getValue();

        $config = array_merge(
            [
                'dataSource' => null,
                'includeSubFolders' => null,
                'category' => null,
                'tags' => [],
                'sortBy' => null,
                'sortMethod' => null,
                'presentAs' => null,
                'limitResult' => null,
                'page' => null,
                'hasNextPage' => null,
                'paginated' => false,
                'categoryRoot' => $params['category_root']->getValue(),
                'categoriesParameter' => $params['categories_parameter']->getValue(),
                'tagsParameter' => $params['tags_parameter']->getValue(),
            ],
            $config
        );

        return $config;
    }

    /**
     * Returns provider for given property.
     *
     * @param PropertyInterface $property
     *
     * @return DataProviderInterface
     */
    private function getProvider(PropertyInterface $property)
    {
        $params = $property->getParams();

        $providerAlias = 'pages';
        if (array_key_exists('provider', $params)) {
            $providerAlias = $params['provider']->getValue();
        }

        return $this->dataProviderPool->get($providerAlias);
    }

    /**
     * Determine current page from current request.
     *
     * @param string $pageParameter
     *
     * @return int
     *
     * @throws PageOutOfBoundsException
     */
    private function getCurrentPage($pageParameter)
    {
        if (null === $this->requestStack->getCurrentRequest()) {
            return 1;
        }

        $page = $this->requestStack->getCurrentRequest()->get($pageParameter, 1);
        if ($page < 1 || $page > PHP_INT_MAX) {
            throw new PageOutOfBoundsException($page);
        }

        return $page;
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (is_string($propertyValue)) {
            return $propertyValue;
        }

        if (is_array($propertyValue)) {
            return json_encode($propertyValue);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * @param $value
     * @param $key
     */
    protected function resolveTags(&$value, $key)
    {
        if (isset($value[$key])) {
            $ids = [];
            $names = [];
            foreach ($value[$key] as $tag) {
                if (is_numeric($tag)) {
                    $ids[] = $tag;
                } else {
                    $names[] = $tag;
                }
            }

            if (!empty($names)) {
                foreach ($this->tagManager->resolveTagNames($names) as $id) {
                    $ids[] = $id;
                }
            }

            $value[$key] = $ids;
        }
    }
}
