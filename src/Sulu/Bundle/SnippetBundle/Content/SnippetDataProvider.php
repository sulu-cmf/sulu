<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\SmartContent\ContentDataItem;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\Util\SuluNodeHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * DataProvider for snippets.
 */
class SnippetDataProvider implements DataProviderInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    public function __construct(
        private ContentQueryExecutorInterface $contentQueryExecutor,
        private ContentQueryBuilderInterface $snippetQueryBuilder,
        private SuluNodeHelper $nodeHelper,
        private LazyLoadingValueHolderFactory $proxyFactory,
        private DocumentManagerInterface $documentManager,
        private ReferenceStoreInterface $referenceStore,
        private bool $hasAudienceTargeting = false,
        private MetadataProviderInterface $formMetadataProvider,
        private ?TokenStorageInterface $tokenStorage = null
    ) {
    }

    public function getConfiguration()
    {
        if (!$this->configuration) {
            $builder = Builder::create()
                ->enableTags()
                ->enableCategories()
                ->enablePresentAs()
                ->enablePagination()
                ->enableLimit()
                ->enableSorting(
                    [
                        ['column' => 'title', 'title' => 'sulu_admin.title'],
                        ['column' => 'created', 'title' => 'sulu_admin.created'],
                        ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ]
                )
                ->enableTypes($this->getTypes())
                ->enableView(SnippetAdmin::EDIT_FORM_VIEW, ['id' => 'id']);

            if ($this->hasAudienceTargeting) {
                $builder->enableAudienceTargeting();
            }

            $this->configuration = $builder->getConfiguration();
        }

        return $this->configuration;
    }

    public function getDefaultPropertyParameter()
    {
        return [];
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

        return new DataProviderResult($this->decorateDataItems($items, $options['locale']), $hasNextPage);
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

        return new DataProviderResult($this->decorateResourceItems($items, $options['locale']), $hasNextPage);
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return null;
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
            SnippetDocument::class,
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
            $metadata = $this->formMetadataProvider->getMetadata('snippet', $user->getLocale(), []);

            foreach ($metadata->getForms() as $form) {
                $types[] = ['type' => $form->getName(), 'title' => $form->getTitle()];
            }
        }

        return $types;
    }

    /**
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return array
     */
    private function resolveFilters(array $filters, array $propertyParameter, array $options, $limit, $page, $pageSize)
    {
        $filters['dataSource'] = $this->nodeHelper->getBaseSnippetUuid(
            \array_key_exists('type', $propertyParameter) ? $propertyParameter['type'] : null
        );
        $filters['includeSubFolders'] = true;

        $properties = \array_key_exists('properties', $propertyParameter)
            ? $propertyParameter['properties']->getValue() : [];

        $excluded = [];

        if (\array_key_exists('excluded', $filters) && \is_array($filters['excluded'])) {
            $excluded = $filters['excluded'];
        }

        if (\array_key_exists('exclude_duplicates', $propertyParameter)
            && $propertyParameter['exclude_duplicates']->getValue()
        ) {
            $excluded = \array_merge($excluded, $this->referenceStore->getAll());
        }

        $this->snippetQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $excluded,
            ]
        );

        $loadLimit = $limit;
        $offset = null;

        if (null !== $pageSize) {
            $offset = ($page - 1) * $pageSize;

            $position = $pageSize * $page;
            if (null !== $limit && $position >= $limit) {
                $pageSize = $limit - $offset;
                $loadLimit = $pageSize;
            } else {
                $loadLimit = $pageSize + 1;
            }
        }

        $items = $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder,
            true,
            -1,
            $loadLimit,
            $offset
        );

        $hasNextPage = false;
        if (null !== $pageSize) {
            $hasNextPage = (\count($items) > $pageSize);
            $items = \array_splice($items, 0, $pageSize);
        }

        return [$items, $hasNextPage];
    }
}
