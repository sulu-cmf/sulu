<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use PHPCR\ItemNotFoundException;
use PHPCR\SessionInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderAliasInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * DataProvider for content.
 */
class PageDataProvider implements DataProviderInterface, DataProviderAliasInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @param bool $showDrafts
     * @param array<string, ?int> $permissions
     */
    public function __construct(
        private ContentQueryBuilderInterface $contentQueryBuilder,
        private ContentQueryExecutorInterface $contentQueryExecutor,
        private DocumentManagerInterface $documentManager,
        private LazyLoadingValueHolderFactory $proxyFactory,
        private SessionInterface $session,
        private ReferenceStoreInterface $referenceStore,
        private $showDrafts,
        private $permissions,
        private bool $hasAudienceTargeting = false,
        private MetadataProviderInterface $formMetadataProvider,
        private ?TokenStorageInterface $tokenStorage = null,
        private array $enabledTwigAttributes = [
            'path' => true,
        ]
    ) {
        if ($this->enabledTwigAttributes['path'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');
        }
    }

    public function getConfiguration()
    {
        if (!$this->configuration) {
            return $this->initConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Initiate configuration.
     *
     * @return ProviderConfigurationInterface
     */
    private function initConfiguration()
    {
        $builder = Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableDatasource('pages', 'pages', 'column_list')
            ->enableSorting(
                [
                    ['column' => null, 'title' => 'sulu_admin.default'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                    ['column' => 'published', 'title' => 'sulu_admin.published'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                ]
            )
            ->enableTypes($this->getTypes())
            ->enableView(PageAdmin::EDIT_FORM_VIEW, ['id' => 'id', 'webspace' => 'webspace']);

        if ($this->hasAudienceTargeting) {
            $builder->enableAudienceTargeting();
        }

        $this->configuration = $builder->getConfiguration();

        return $this->configuration;
    }

    public function getDefaultPropertyParameter()
    {
        return [
            'properties' => new PropertyParameter('properties', [], 'collection'),
        ];
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        $properties = \array_key_exists('properties', $propertyParameter) ?
            $propertyParameter['properties']->getValue() : [];

        $this->contentQueryBuilder->init(
            [
                'ids' => [$datasource],
                'properties' => $properties,
                'published' => false,
            ]
        );

        $result = $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder,
            true,
            -1,
            1,
            0,
            false
        );

        if (0 === \count($result)) {
            return null;
        }

        return new DatasourceItem($result[0]['id'], $result[0]['title'], $result[0]['url'] ?? null);
    }

    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($items, $hasNextPage) = $this->resolveFilters(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );

        $items = $this->decorateDataItems($items, $options['locale']);

        return new DataProviderResult($items, $hasNextPage);
    }

    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($items, $hasNextPage) = $this->resolveFilters(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );
        $items = $this->decorateResourceItems($items, $options['locale']);

        return new DataProviderResult($items, $hasNextPage);
    }

    /**
     * Resolves filters.
     */
    private function resolveFilters(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $emptyFilterResult = [[], false];

        if (!\array_key_exists('dataSource', $filters)
            || null === $filters['dataSource']
            || '' === $filters['dataSource']
            || (null !== $limit && $limit < 1)
        ) {
            return $emptyFilterResult;
        }

        try {
            $this->session->getNodeByIdentifier($filters['dataSource']);
        } catch (ItemNotFoundException $e) {
            return $emptyFilterResult;
        }

        $properties = \array_key_exists('properties', $propertyParameter) ?
            $propertyParameter['properties']->getValue() : [];

        $excluded = isset($filters['excluded']) ? $filters['excluded'] : [];
        if (\array_key_exists('exclude_duplicates', $propertyParameter)
            && $propertyParameter['exclude_duplicates']->getValue()
        ) {
            $excluded = \array_merge($excluded, $this->referenceStore->getAll());
        }

        $this->contentQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $excluded,
                'published' => !$this->showDrafts,
            ]
        );

        $hasNextPage = false;
        if (null !== $pageSize) {
            $result = $this->loadPaginated($options, $limit, $page, $pageSize);
            $hasNextPage = (\count($result) > $pageSize);
            $items = \array_splice($result, 0, $pageSize);
        } else {
            $items = $this->load($options, $limit);
        }

        return [$items, $hasNextPage];
    }

    /**
     * Load paginated data.
     *
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return array
     */
    private function loadPaginated(array $options, $limit, $page, $pageSize)
    {
        $pageSize = \intval($pageSize);
        $offset = ($page - 1) * $pageSize;

        $position = $pageSize * $page;
        if (null !== $limit && $position >= $limit) {
            $pageSize = $limit - $offset;
            $loadLimit = $pageSize;
        } else {
            $loadLimit = $pageSize + 1;
        }

        return $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder,
            true,
            -1,
            $loadLimit,
            $offset,
            false,
            $this->permissions[PermissionTypes::VIEW]
        );
    }

    /**
     * Load data.
     *
     * @param int $limit
     *
     * @return array
     */
    private function load(array $options, $limit)
    {
        return $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder,
            true,
            -1,
            $limit,
            null,
            false,
            $this->permissions[PermissionTypes::VIEW]
        );
    }

    /**
     * Decorates result with item class.
     *
     * @param string $locale
     *
     * @return ContentDataItem[]
     */
    private function decorateDataItems(array $data, $locale)
    {
        return \array_map(
            function($item) use ($locale) {
                return new ContentDataItem($item, $this->getResource($item['id'], $locale));
            },
            $data
        );
    }

    /**
     * Decorates result with item class.
     *
     * @param string $locale
     *
     * @return ArrayAccessItem[]
     */
    private function decorateResourceItems(array $data, $locale)
    {
        return \array_map(
            function($item) use ($locale) {
                $this->referenceStore->add($item['id']);

                if (!($this->enabledTwigAttributes['path'] ?? true)) {
                    unset($item['path']);
                }

                return new ArrayAccessItem($item['id'], $item, $this->getResource($item['id'], $locale));
            },
            $data
        );
    }

    /**
     * Returns Proxy Document for uuid.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return object
     */
    private function getResource($uuid, $locale)
    {
        return $this->proxyFactory->createProxy(
            PageDocument::class,
            function(
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($uuid, $locale) {
                $initializer = null;
                $wrappedObject = $this->documentManager->find($uuid, $locale);

                return true;
            }
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getTypes(): array
    {
        $types = [];
        if ($this->tokenStorage && null !== $this->tokenStorage->getToken() && $this->formMetadataProvider) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user instanceof UserInterface) {
                return $types;
            }

            /** @var TypedFormMetadata $metadata */
            $metadata = $this->formMetadataProvider->getMetadata('page', $user->getLocale(), []);

            foreach ($metadata->getForms() as $form) {
                $types[] = ['type' => $form->getName(), 'title' => $form->getTitle()];
            }
        }

        return $types;
    }

    public function getAlias()
    {
        return 'content';
    }
}
