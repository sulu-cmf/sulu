<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Media DataProvider for SmartContent.
 */
class MediaDataProvider extends BaseDataProvider
{
    public function __construct(
        DataProviderRepositoryInterface $repository,
        private CollectionManagerInterface $collectionManager,
        ArraySerializerInterface $serializer,
        private RequestStack $requestStack,
        ReferenceStoreInterface $referenceStore,
        Security|SymfonyCoreSecurity|null $security,
        RequestAnalyzerInterface $requestAnalyzer,
        $permissions,
        private bool $hasAudienceTargeting = false,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {
        parent::__construct($repository, $serializer, $referenceStore, $security, $requestAnalyzer, $permissions);
    }

    public function getConfiguration()
    {
        if (!$this->configuration) {
            $builder = self::createConfigurationBuilder()
                ->enableTags()
                ->enableCategories()
                ->enableLimit()
                ->enablePagination()
                ->enablePresentAs()
                ->enableDatasource('collections', 'collections', 'column_list')
                ->enableSorting(
                    [
                        ['column' => 'fileVersionMeta.title', 'title' => 'sulu_admin.title'],
                        ['column' => 'created', 'title' => 'sulu_admin.created'],
                        ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ]
                )
                ->enableTypes($this->getTypes())
                ->enableView(MediaAdmin::EDIT_FORM_VIEW, ['id' => 'id']);

            if ($this->hasAudienceTargeting) {
                $builder->enableAudienceTargeting();
            }

            $this->configuration = $builder->getConfiguration();
        }

        return $this->configuration;
    }

    public function getDefaultPropertyParameter()
    {
        return [
            'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
            'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
        ];
    }

    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        if (($filters['dataSource'] ?? null) === null) {
            return new DataProviderResult([], false);
        }

        return parent::resolveDataItems($filters, $propertyParameter, $options, $limit, $page, $pageSize);
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        if (empty($datasource)) {
            return null;
        }

        if ('root' === $datasource) {
            $title = 'smart-content.media.all-collections';

            return new DatasourceItem('root', $title, $title);
        }

        $entity = $this->collectionManager->getById($datasource, $options['locale']);

        return new DatasourceItem($entity->getId(), (string) $entity->getTitle(), (string) $entity->getTitle());
    }

    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        if (($filters['dataSource'] ?? null) === null) {
            return new DataProviderResult([], false);
        }

        return parent::resolveResourceItems($filters, $propertyParameter, $options, $limit, $page, $pageSize);
    }

    protected function getOptions(
        array $propertyParameter,
        array $options = []
    ) {
        $request = $this->requestStack->getCurrentRequest();

        $queryOptions = [];

        if (\array_key_exists('mimetype_parameter', $propertyParameter)) {
            $queryOptions['mimetype'] = $request->get($propertyParameter['mimetype_parameter']->getValue());
        }
        if (\array_key_exists('type_parameter', $propertyParameter)) {
            $queryOptions['type'] = $request->get($propertyParameter['type_parameter']->getValue());
        }

        return \array_merge($options, \array_filter($queryOptions));
    }

    protected function decorateDataItems(array $data)
    {
        return \array_map(
            function($item) {
                return new MediaDataItem($item);
            },
            $data
        );
    }

    protected function getSerializationContext()
    {
        $serializationContext = parent::getSerializationContext();

        $serializationContext->setGroups(['Default']);

        return $serializationContext;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function getTypes(): array
    {
        $types = [];

        if (!$this->entityManager) {
            return $types;
        }

        $repository = $this->entityManager->getRepository(MediaType::class);
        /** @var MediaType $mediaType */
        foreach ($repository->findAll() as $mediaType) {
            $title = $this->translator ? $this->translator->trans('sulu_media.' . $mediaType->getName(), [], 'admin') : $mediaType->getName();
            $types[] = ['type' => $mediaType->getId(), 'title' => $title];
        }

        return $types;
    }
}
