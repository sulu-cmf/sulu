<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use Jackalope\Query\QOM\PropertyValue;
use Jackalope\Query\Row;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\RowInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\DescendantProviderInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Content repository which query content with sql2 statements.
 */
class ContentRepository implements ContentRepositoryInterface, DescendantProviderInterface
{
    private static $nonFallbackProperties = [
        'uuid',
        'state',
        'order',
        'created',
        'creator',
        'changed',
        'changer',
        'published',
        'shadowOn',
        'shadowBase',
    ];

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var QueryObjectModelFactoryInterface
     */
    private $qomFactory;

    public function __construct(
        private SessionManagerInterface $sessionManager,
        private PropertyEncoder $propertyEncoder,
        private WebspaceManagerInterface $webspaceManager,
        private LocalizationFinderInterface $localizationFinder,
        private StructureManagerInterface $structureManager,
        private SuluNodeHelper $nodeHelper,
        private SystemStoreInterface $systemStore,
        private array $permissions
    ) {
        $this->session = $this->sessionManager->getSession();
        $this->qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
    }

    /**
     * Find content by uuid.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     *
     * @return Content|null
     */
    public function find($uuid, $locale, $webspaceKey, MappingInterface $mapping, ?UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where(
            $this->qomFactory->comparison(
                new PropertyValue('node', 'jcr:uuid'),
                '=',
                $this->qomFactory->literal($uuid)
            )
        );
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        $queryResult = $queryBuilder->execute();

        $rows = \iterator_to_array($queryResult->getRows());
        if (1 !== \count($rows)) {
            throw new ItemNotFoundException();
        }

        $resultPermissions = $this->resolveResultPermissions($rows, $user);
        $permissions = empty($resultPermissions) ? [] : \current($resultPermissions);

        return $this->resolveContent(\current($rows), $locale, $locales, $mapping, $user, $permissions);
    }

    public function findByParentUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        ?UserInterface $user = null
    ) {
        $path = $this->resolvePathByUuid($uuid);

        if (!$webspaceKey) {
            // TODO find a better solution than this (e.g. reuse logic from DocumentInspector and preferably in the PageController)
            $webspaceKey = \explode('/', $path)[2];
        }

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where($this->qomFactory->childNode('node', $path));
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    public function findByWebspaceRoot($locale, $webspaceKey, MappingInterface $mapping, ?UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where(
            $this->qomFactory->childNode('node', $this->sessionManager->getContentPath($webspaceKey))
        );
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    public function findParentsWithSiblingsByUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        ?UserInterface $user = null
    ) {
        $path = $this->resolvePathByUuid($uuid);
        if (empty($webspaceKey)) {
            $webspaceKey = $this->nodeHelper->extractWebspaceFromPath($path);
        }

        $contentPath = $this->sessionManager->getContentPath($webspaceKey);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->orderBy($this->qomFactory->propertyValue('node', 'jcr:path'))
            ->where($this->qomFactory->childNode('node', $path));

        while (PathHelper::getPathDepth($path) > PathHelper::getPathDepth($contentPath)) {
            $path = PathHelper::getParentPath($path);
            $queryBuilder->orWhere($this->qomFactory->childNode('node', $path));
        }

        $mapping->addProperties(['order']);
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        $result = $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);

        return $this->generateTreeByPath($result, $uuid);
    }

    public function findByPaths(
        array $paths,
        $locale,
        MappingInterface $mapping,
        ?UserInterface $user = null
    ) {
        $locales = $this->getLocales();
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);

        foreach ($paths as $path) {
            $queryBuilder->orWhere(
                $this->qomFactory->sameNode('node', $path)
            );
        }
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    public function findByUuids(
        array $uuids,
        $locale,
        MappingInterface $mapping,
        ?UserInterface $user = null
    ) {
        if (0 === \count($uuids)) {
            return [];
        }

        $locales = $this->getLocales();
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);

        foreach ($uuids as $uuid) {
            $queryBuilder->orWhere(
                $this->qomFactory->comparison(
                    $queryBuilder->qomf()->propertyValue('node', 'jcr:uuid'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                    $queryBuilder->qomf()->literal($uuid)
                )
            );
        }
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        $result = $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);

        \usort($result, function($a, $b) use ($uuids) {
            return \array_search($a->getId(), $uuids) < \array_search($b->getId(), $uuids) ? -1 : 1;
        });

        return $result;
    }

    public function findAll($locale, $webspaceKey, MappingInterface $mapping, ?UserInterface $user = null)
    {
        $contentPath = $this->sessionManager->getContentPath($webspaceKey);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->where($this->qomFactory->descendantNode('node', $contentPath))
            ->orWhere($this->qomFactory->sameNode('node', $contentPath));

        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    public function findAllByPortal($locale, $portalKey, MappingInterface $mapping, ?UserInterface $user = null)
    {
        $webspaceKey = $this->webspaceManager->findPortalByKey($portalKey)->getWebspace()->getKey();

        $contentPath = $this->sessionManager->getContentPath($webspaceKey);

        $locales = $this->getLocalesByPortalKey($portalKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->where($this->qomFactory->descendantNode('node', $contentPath))
            ->orWhere($this->qomFactory->sameNode('node', $contentPath));

        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    public function findDescendantIdsById($id)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->where(
            $this->qomFactory->comparison(
                new PropertyValue('node', 'jcr:uuid'),
                '=',
                $this->qomFactory->literal($id)
            )
        );

        $result = \iterator_to_array($queryBuilder->execute());

        if (0 === \count($result)) {
            return [];
        }

        $path = $result[0]->getPath();

        $descendantQueryBuilder = $this->getQueryBuilder()
            ->where($this->qomFactory->descendantNode('node', $path));

        return \array_map(
            function(RowInterface $row) {
                return $row->getNode()->getIdentifier();
            },
            \iterator_to_array($descendantQueryBuilder->execute())
        );
    }

    /**
     * Generates a content-tree with paths of given content array.
     *
     * @param Content[] $contents
     *
     * @return Content[]
     */
    private function generateTreeByPath(array $contents, $uuid)
    {
        $childrenByPath = [];

        foreach ($contents as $content) {
            $path = PathHelper::getParentPath($content->getPath());
            if (!isset($childrenByPath[$path])) {
                $childrenByPath[$path] = [];
            }

            $order = $content['order'];
            while (isset($childrenByPath[$path][$order])) {
                ++$order;
            }

            $childrenByPath[$path][$order] = $content;
        }

        foreach ($contents as $content) {
            if (!isset($childrenByPath[$content->getPath()])) {
                if ($content->getId() === $uuid) {
                    $content->setChildren([]);
                }

                continue;
            }

            \ksort($childrenByPath[$content->getPath()]);

            $content->setChildren(\array_values($childrenByPath[$content->getPath()]));
        }

        if (!\array_key_exists('/', $childrenByPath) || !\is_array($childrenByPath['/'])) {
            return [];
        }

        \ksort($childrenByPath['/']);

        return \array_values($childrenByPath['/']);
    }

    /**
     * Resolve path for node with given uuid.
     *
     * @param string $uuid
     *
     * @return string
     *
     * @throws ItemNotFoundException
     */
    private function resolvePathByUuid($uuid)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->where(
                $this->qomFactory->comparison(
                    $this->qomFactory->propertyValue('node', 'jcr:uuid'),
                    '=',
                    $this->qomFactory->literal($uuid)
                )
            );

        $rows = $queryBuilder->execute();

        if (1 !== \count(\iterator_to_array($rows->getRows()))) {
            throw new ItemNotFoundException();
        }

        return $rows->getRows()->current()->getPath();
    }

    /**
     * Resolves query results to content.
     *
     * @param string $locale
     *
     * @return Content[]
     */
    private function resolveQueryBuilder(
        QueryBuilder $queryBuilder,
        $locale,
        $locales,
        MappingInterface $mapping,
        ?UserInterface $user = null
    ) {
        $result = \iterator_to_array($queryBuilder->execute());

        $permissions = $this->resolveResultPermissions($result, $user);

        return \array_values(
            \array_filter(
                \array_map(
                    function(RowInterface $row, $index) use ($mapping, $locale, $locales, $user, $permissions) {
                        return $this->resolveContent(
                            $row,
                            $locale,
                            $locales,
                            $mapping,
                            $user,
                            $permissions[$index] ?? []
                        );
                    },
                    $result,
                    \array_keys($result)
                )
            )
        );
    }

    private function resolveResultPermissions(array $result, ?UserInterface $user = null)
    {
        $permissions = [];

        foreach ($result as $index => $row) {
            $permissions[$index] = [];

            $jsonPermission = $row->getValue(SecuritySubscriber::SECURITY_PERMISSION_PROPERTY);

            if (!$jsonPermission) {
                continue;
            }

            $rowPermissions = \json_decode($jsonPermission, true);

            foreach ($rowPermissions as $roleId => $rolePermissions) {
                foreach ($this->permissions as $permissionKey => $permission) {
                    $permissions[$index][$roleId][$permissionKey] = false;
                }

                foreach ($rolePermissions as $rolePermission) {
                    $permissions[$index][$roleId][$rolePermission] = true;
                }
            }
        }

        return $permissions;
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $locale
     * @param string[] $locales
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($locale = null, $locales = [], ?UserInterface $user = null)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->addSelect('node', $this->getPropertyName('nodeType', $locale), 'nodeType')
            ->addSelect('node', $this->getPropertyName('internal_link', $locale), 'internalLink')
            ->addSelect('node', $this->getPropertyName('state', $locale), 'state')
            ->addSelect('node', $this->getPropertyName('shadow-on', $locale), 'shadowOn')
            ->addSelect('node', $this->getPropertyName('shadow-base', $locale), 'shadowBase')
            ->addSelect('node', $this->propertyEncoder->systemName('order'), 'order')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->orderBy($this->qomFactory->propertyValue('node', 'sulu:order'));

        $this->appendSingleMapping($queryBuilder, 'template', $locales);
        $this->appendSingleMapping($queryBuilder, 'shadow-on', $locales);
        $this->appendSingleMapping($queryBuilder, 'state', $locales);

        $queryBuilder->addSelect(
            'node',
            SecuritySubscriber::SECURITY_PERMISSION_PROPERTY,
            SecuritySubscriber::SECURITY_PERMISSION_PROPERTY
        );

        return $queryBuilder;
    }

    private function getPropertyName(string $propertyName, $locale): string
    {
        if ($locale) {
            return $this->propertyEncoder->localizedContentName($propertyName, $locale);
        }

        return $this->propertyEncoder->contentName($propertyName);
    }

    /**
     * Returns array of locales for given webspace key.
     *
     * @param string $webspaceKey
     *
     * @return string[]
     */
    private function getLocalesByWebspaceKey($webspaceKey)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        return \array_map(
            function(Localization $localization) {
                return $localization->getLocale();
            },
            $webspace->getAllLocalizations()
        );
    }

    /**
     * Returns array of locales for given portal key.
     *
     * @param string $portalKey
     *
     * @return string[]
     */
    private function getLocalesByPortalKey($portalKey)
    {
        $portal = $this->webspaceManager->findPortalByKey($portalKey);

        return \array_map(
            function(Localization $localization) {
                return $localization->getLocale();
            },
            $portal->getLocalizations()
        );
    }

    /**
     * Returns array of locales for webspaces.
     *
     * @return string[]
     */
    private function getLocales()
    {
        return $this->webspaceManager->getAllLocales();
    }

    /**
     * Append mapping selects to given query-builder.
     *
     * @param MappingInterface $mapping Includes array of property names
     * @param string $locale
     * @param string[] $locales
     */
    private function appendMapping(QueryBuilder $queryBuilder, MappingInterface $mapping, $locale, $locales)
    {
        if ($mapping->onlyPublished()) {
            $queryBuilder->andWhere(
                $this->qomFactory->comparison(
                    $this->qomFactory->propertyValue(
                        'node',
                        $this->propertyEncoder->localizedSystemName('state', $locale)
                    ),
                    '=',
                    $this->qomFactory->literal(WorkflowStage::PUBLISHED)
                )
            );
        }

        $properties = $mapping->getProperties();
        foreach ($properties as $propertyName) {
            $this->appendSingleMapping($queryBuilder, $propertyName, $locales);
        }

        if ($mapping->resolveUrl()) {
            $this->appendUrlMapping($queryBuilder, $locales);
        }
    }

    /**
     * Append mapping selects for a single property to given query-builder.
     *
     * @param string $propertyName
     * @param string[] $locales
     */
    private function appendSingleMapping(QueryBuilder $queryBuilder, $propertyName, $locales)
    {
        foreach ($locales as $locale) {
            $alias = \sprintf('%s%s', $locale, \str_replace('-', '_', \ucfirst($propertyName)));

            $queryBuilder->addSelect(
                'node',
                $this->propertyEncoder->localizedContentName($propertyName, $locale),
                $alias
            );
        }
    }

    /**
     * Append mapping for url to given query-builder.
     *
     * @param string[] $locales
     */
    private function appendUrlMapping(QueryBuilder $queryBuilder, $locales)
    {
        $structures = $this->structureManager->getStructures(Structure::TYPE_PAGE);
        $urlNames = [];

        foreach ($structures as $structure) {
            if (!$structure->hasTag('sulu.rlp')) {
                continue;
            }

            $propertyName = $structure->getPropertyByTagName('sulu.rlp')->getName();

            if (!\in_array($propertyName, $urlNames)) {
                $this->appendSingleMapping($queryBuilder, $propertyName, $locales);
                $urlNames[] = $propertyName;
            }
        }
    }

    /**
     * Resolve a single result row to a content object.
     *
     * @param string $locale
     * @param string $locales
     *
     * @return Content|null
     */
    private function resolveContent(
        RowInterface $row,
        $locale,
        $locales,
        MappingInterface $mapping,
        ?UserInterface $user = null,
        array $permissions = []
    ) {
        $webspaceKey = $this->nodeHelper->extractWebspaceFromPath($row->getPath());

        $originalLocale = $locale;
        $availableLocales = $this->resolveAvailableLocales($row);
        $ghostLocale = $this->localizationFinder->findAvailableLocale(
            $webspaceKey,
            $availableLocales,
            $locale
        );
        if (null === $ghostLocale) {
            $ghostLocale = \reset($availableLocales);
        }

        $type = null;
        if ($row->getValue('shadowOn')) {
            if (!$mapping->shouldHydrateShadow()) {
                return null;
            }
            $type = StructureType::getShadow($row->getValue('shadowBase'));
        } elseif (null !== $ghostLocale && $ghostLocale !== $originalLocale) {
            if (!$mapping->shouldHydrateGhost()) {
                return null;
            }
            $locale = $ghostLocale;
            $type = StructureType::getGhost($locale);
        }

        if (
            RedirectType::INTERNAL === $row->getValue('nodeType')
            && $mapping->followInternalLink()
            && '' !== $row->getValue('internalLink')
            && $row->getValue('internalLink') !== $row->getValue('uuid')
        ) {
            // TODO collect all internal link contents and query once
            return $this->resolveInternalLinkContent($row, $locale, $webspaceKey, $mapping, $type, $user);
        }

        $shadowBase = null;
        if ($row->getValue('shadowOn')) {
            $shadowBase = $row->getValue('shadowBase');
        }

        $data = [];
        foreach ($mapping->getProperties() as $item) {
            $data[$item] = $this->resolveProperty($row, $item, $locale, $shadowBase);
        }

        $content = new Content(
            $originalLocale,
            $webspaceKey,
            $row->getValue('uuid'),
            $this->resolvePath($row, $webspaceKey),
            $row->getValue('state'),
            $row->getValue('nodeType'),
            $this->resolveHasChildren($row), $this->resolveProperty($row, 'template', $locale, $shadowBase),
            $data,
            $permissions,
            $type
        );
        $content->setRow($row);

        if (!$content->getTemplate() || !$this->structureManager->getStructure($content->getTemplate())) {
            $content->setBrokenTemplate();
        }

        if ($mapping->resolveUrl()) {
            $url = $this->resolveUrl($row, $locale);
            /** @var array<string, string|null> $urls */
            $urls = [];
            \array_walk(
                $locales,
                /** @var array<string, string|null> $urls */
                function($locale) use (&$urls, $row) {
                    $urls[$locale] = $this->resolveUrl($row, $locale);
                }
            );

            $content->setUrl($url);
            $content->setUrls($urls);
        }

        if ($mapping->resolveConcreteLocales()) {
            $locales = $this->resolveAvailableLocales($row);
            $content->setContentLocales($locales);
        }

        return $content;
    }

    /**
     * Resolves all available localizations for given row.
     *
     * @return string[]
     */
    private function resolveAvailableLocales(RowInterface $row)
    {
        $locales = [];
        foreach ($row->getValues() as $key => $value) {
            if (\preg_match('/^node.([a-zA-Z_]*?)Template/', $key, $matches) && '' !== $value
                && !$row->getValue(\sprintf('node.%sShadow_on', $matches[1]))
            ) {
                $locales[] = $matches[1];
            }
        }

        return $locales;
    }

    /**
     * Resolve a single result row which is an internal link to a content object.
     *
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     *
     * @return Content|null
     */
    public function resolveInternalLinkContent(
        RowInterface $row,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        ?StructureType $type = null,
        ?UserInterface $user = null
    ) {
        $linkedContent = $this->find($row->getValue('internalLink'), $locale, $webspaceKey, $mapping);
        if (null === $linkedContent) {
            return null;
        }

        $data = $linkedContent->getData();

        // return value of source node instead of link destination for title and non-fallback-properties
        $sourceNodeValueProperties = self::$nonFallbackProperties;
        $sourceNodeValueProperties[] = 'title';
        $properties = \array_intersect($sourceNodeValueProperties, \array_keys($data));
        foreach ($properties as $property) {
            $data[$property] = $this->resolveProperty($row, $property, $locale);
        }

        $resultPermissions = $this->resolveResultPermissions([$row], $user);
        $permissions = empty($resultPermissions) ? [] : \current($resultPermissions);

        $content = new Content(
            $locale,
            $webspaceKey,
            $row->getValue('uuid'),
            $this->resolvePath($row, $webspaceKey),
            $row->getValue('state'),
            $row->getValue('nodeType'),
            $this->resolveHasChildren($row), $this->resolveProperty($row, 'template', $locale),
            $data,
            $permissions,
            $type
        );

        if ($mapping->resolveUrl()) {
            $content->setUrl($linkedContent->getUrl());
            $content->setUrls($linkedContent->getUrls());
        }

        if (!$content->getTemplate() || !$this->structureManager->getStructure($content->getTemplate())) {
            $content->setBrokenTemplate();
        }

        return $content;
    }

    /**
     * Resolve a property and follow shadow locale if it has one.
     *
     * @param string $name
     * @param string $locale
     * @param string $shadowLocale
     */
    private function resolveProperty(RowInterface $row, $name, $locale, $shadowLocale = null)
    {
        if (\array_key_exists(\sprintf('node.%s', $name), $row->getValues())) {
            return $row->getValue($name);
        }

        if (null !== $shadowLocale && !\in_array($name, self::$nonFallbackProperties)) {
            $locale = $shadowLocale;
        }

        $name = \sprintf('%s%s', $locale, \str_replace('-', '_', \ucfirst($name)));

        try {
            return $row->getValue($name);
        } catch (ItemNotFoundException $e) {
            // the default value of a non existing property in jackalope is an empty string
            return '';
        }
    }

    /**
     * Resolve url property.
     *
     * @param string $locale
     *
     * @return string|null
     */
    private function resolveUrl(RowInterface $row, $locale)
    {
        if (WorkflowStage::PUBLISHED !== $this->resolveProperty($row, $locale . 'State', $locale)) {
            return null;
        }

        $template = $this->resolveProperty($row, 'template', $locale);
        if (empty($template)) {
            return null;
        }

        $structure = $this->structureManager->getStructure($template);
        if (!$structure || !$structure->hasTag('sulu.rlp')) {
            return null;
        }

        $propertyName = $structure->getPropertyByTagName('sulu.rlp')->getName();

        return $this->resolveProperty($row, $propertyName, $locale);
    }

    /**
     * Resolves path for given row.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function resolvePath(RowInterface $row, $webspaceKey)
    {
        return '/' . \ltrim(\str_replace($this->sessionManager->getContentPath($webspaceKey), '', $row->getPath()), '/');
    }

    /**
     * Resolve property has-children with given node.
     *
     * @return bool
     */
    private function resolveHasChildren(RowInterface $row)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->where($this->qomFactory->childNode('node', $row->getPath()))
            ->setMaxResults(1);

        $result = $queryBuilder->execute();

        return \count(\iterator_to_array($result->getRows())) > 0;
    }

    public function supportsDescendantType(string $type): bool
    {
        try {
            $class = new \ReflectionClass($type);
        } catch (\ReflectionException $e) {
            // in case the class does not exist there is no support
            return false;
        }

        return $class->implementsInterface(SecurityBehavior::class);
    }
}
