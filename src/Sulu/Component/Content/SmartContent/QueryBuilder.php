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

use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Query builder to load smart content.
 */
class QueryBuilder extends ContentQueryBuilder
{
    /**
     * disable automatic excerpt loading.
     *
     * @var bool
     */
    protected $excerpt = false;

    /**
     * configuration which properties should be loaded.
     *
     * @var array
     */
    private $propertiesConfig = [];

    /**
     * configuration of.
     *
     * @var array
     */
    private $config = [];

    /**
     * array of ids to load.
     *
     * @var array
     */
    private $ids = [];

    /**
     * array of excluded pages.
     *
     * @var array
     */
    private $excluded = [];

    /**
     * @var string
     */
    protected static $structureType = Structure::TYPE_PAGE;

    public function __construct(
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        private SessionManagerInterface $sessionManager,
        $languageNamespace
    ) {
        parent::__construct($structureManager, $extensionManager, $languageNamespace);
    }

    protected function buildWhere($webspaceKey, $locale)
    {
        $sql2Where = [];
        // build where clause for datasource
        if ($this->hasConfig('dataSource')) {
            $sql2Where[] = $this->buildDatasourceWhere();
        } elseif (0 === \count($this->ids)) {
            $sql2Where[] = \sprintf(
                'ISDESCENDANTNODE(page, "/cmf/%s/contents")',
                $webspaceKey
            );
        }

        if ($this->hasConfig('audienceTargeting')
            && $this->getConfig('audienceTargeting', false)
            && $this->hasConfig('targetGroupId')
        ) {
            $result = $this->buildAudienceTargeting($this->getConfig('targetGroupId'), $locale);

            if ($result) {
                $sql2Where[] = $result;
            }
        }

        if ($this->hasConfig('segmentKey')) {
            $result = $this->buildSegmentKey($webspaceKey, $this->getConfig('segmentKey'), $locale);

            if ($result) {
                $sql2Where[] = $result;
            }
        }

        // build where clause for tags
        if ($this->hasConfig('tags')) {
            $sql2Where[] = $this->buildTagsWhere(
                $this->getConfig('tags', []),
                $this->getConfig('tagOperator', 'OR'),
                $locale
            );
        }

        // build where clause for website tags
        if ($this->hasConfig('websiteTags')) {
            $sql2Where[] = $this->buildTagsWhere(
                $this->getConfig('websiteTags', []),
                $this->getConfig('websiteTagsOperator', 'OR'),
                $locale
            );
        }

        // build where clause for types
        if ($this->hasConfig('types')) {
            $sql2Where[] = $this->buildTypesWhere(
                $this->getConfig('types', []),
                $locale
            );
        }

        // build where clause for categories
        if ($this->hasConfig('categories')) {
            $sql2Where[] = $this->buildCategoriesWhere(
                $this->getConfig('categories', []),
                $this->getConfig('categoryOperator', 'OR'),
                $locale
            );
        }

        // build where clause for website categories
        if ($this->hasConfig('websiteCategories')) {
            $sql2Where[] = $this->buildCategoriesWhere(
                $this->getConfig('websiteCategories', []),
                $this->getConfig('websiteCategoriesOperator', 'OR'),
                $locale
            );
        }

        if (\count($this->ids) > 0) {
            $sql2Where[] = $this->buildPageSelector();
        }

        if (\count($this->excluded) > 0) {
            $sql2Where = \array_merge($sql2Where, $this->buildPageExclude());
        }

        $sql2Where = \array_filter($sql2Where);

        return \implode(' AND ', $sql2Where);
    }

    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        $select = [];

        if (\count($this->propertiesConfig) > 0) {
            $this->buildPropertiesSelect($locale, $additionalFields);
        }

        return \implode(', ', $select);
    }

    protected function buildOrder($webspaceKey, $locale)
    {
        $sortOrder = (isset($this->config['sortMethod']) && 'desc' === \strtolower($this->config['sortMethod']))
            ? 'DESC' : 'ASC';

        $sql2Order = [];
        $sortBy = $this->getConfig('sortBy');

        if ($sortBy) {
            $order = 'page.[i18n:' . $locale . '-' . $sortBy . '] ';
            if (!\in_array($sortBy, ['published', 'created', 'changed', 'authored'])) {
                $order = \sprintf('lower(%s)', $order);
            }

            $sql2Order[] = $order . $sortOrder;
        } elseif (!$this->getConfig('includeSubFolders', false)) {
            $sql2Order[] = 'page.[sulu:order] ' . $sortOrder;
        }

        return \implode(', ', $sql2Order);
    }

    public function init(array $options)
    {
        $this->propertiesConfig = isset($options['properties']) ? $options['properties'] : [];
        $this->ids = isset($options['ids']) ? $options['ids'] : [];
        $this->config = isset($options['config']) ? $options['config'] : [];
        $this->excluded = isset($options['excluded']) ? $options['excluded'] : [];
        $this->published = isset($options['published']) ? $options['published'] : false;
    }

    /**
     * build select for properties.
     */
    private function buildPropertiesSelect($locale, &$additionalFields)
    {
        foreach ($this->propertiesConfig as $parameter) {
            $alias = $parameter->getName();
            $propertyName = $parameter->getValue();

            if (false !== \strpos($propertyName, '.')) {
                $parts = \explode('.', $propertyName);

                $this->buildExtensionSelect($alias, $parts[0], $parts[1], $locale, $additionalFields);
            } else {
                $this->buildPropertySelect($alias, $propertyName, $locale, $additionalFields);
            }
        }
    }

    /**
     * build select for single property.
     */
    private function buildPropertySelect($alias, $propertyName, $locale, &$additionalFields)
    {
        foreach ($this->structureManager->getStructures(static::$structureType) as $structure) {
            if ($structure->hasProperty($propertyName)) {
                $property = $structure->getProperty($propertyName);
                $additionalFields[$locale][] = [
                    'name' => $alias,
                    'property' => $property,
                    'templateKey' => $structure->getKey(),
                ];
            }
        }
    }

    /**
     * build select for extension property.
     */
    private function buildExtensionSelect($alias, $extension, $propertyName, $locale, &$additionalFields)
    {
        $extension = $this->extensionManager->getExtension('all', $extension);
        $additionalFields[$locale][] = [
            'name' => $alias,
            'extension' => $extension,
            'property' => $propertyName,
        ];
    }

    /**
     * build datasource where clause.
     */
    private function buildDatasourceWhere()
    {
        $dataSource = $this->getConfig('dataSource');
        $includeSubFolders = $this->getConfig('includeSubFolders', false);
        $sqlFunction = false !== $includeSubFolders && 'false' !== $includeSubFolders ?
            'ISDESCENDANTNODE' : 'ISCHILDNODE';

        $node = $this->sessionManager->getSession()->getNodeByIdentifier($dataSource);

        return $sqlFunction . '(page, \'' . $node->getPath() . '\')';
    }

    /**
     * Returns the where part for the audience targeting.
     *
     * @param string $targetGroupId
     * @param string $locale
     *
     * @return string
     */
    private function buildAudienceTargeting($targetGroupId, $locale)
    {
        if (!$targetGroupId) {
            return;
        }

        $structure = $this->structureManager->getStructure('excerpt');

        $property = new TranslatedProperty(
            $structure->getProperty('audience_targeting_groups'),
            $locale,
            $this->languageNamespace,
            'excerpt'
        );

        return 'page.[' . $property->getName() . '] = ' . $targetGroupId;
    }

    private function buildSegmentKey($webspaceKey, $segmentKey, $locale)
    {
        if (!$segmentKey) {
            return;
        }

        $structure = $this->structureManager->getStructure('excerpt');

        $property = new TranslatedProperty(
            $structure->getProperty('segments'),
            $locale,
            $this->languageNamespace,
            'excerpt'
        );

        $webspaceSegmentPropertyName = $property->getName() . SegmentSelect::SEPARATOR . $webspaceKey;
        $column = 'page.[' . $webspaceSegmentPropertyName . ']';

        return '(' . $column . ' = "' . $segmentKey . '" OR ' . $column . ' IS NULL)';
    }

    /**
     * build tags where clauses.
     */
    protected function buildTagsWhere($tags, $operator, $languageCode)
    {
        $structure = $this->structureManager->getStructure('excerpt');

        $sql2Where = [];
        if ($structure->hasProperty('tags')) {
            $property = new TranslatedProperty(
                $structure->getProperty('tags'),
                $languageCode,
                $this->languageNamespace,
                'excerpt'
            );

            foreach ($tags as $tag) {
                $sql2Where[] = 'page.[' . $property->getName() . '] = ' . $tag;
            }

            if (\count($sql2Where) > 0) {
                return '(' . \implode(' ' . \strtoupper($operator) . ' ', $sql2Where) . ')';
            }
        }

        return '';
    }

    /**
     * @param array<string>|string $types
     *
     * @return string
     */
    protected function buildTypesWhere($types, string $languageCode)
    {
        $sql2Where = [];
        foreach ($types as $type) {
            $sql2Where[] = \sprintf('page.[i18n:%s-template] = \'%s\'', $languageCode, $type);
        }

        if (\count($sql2Where) > 0) {
            return '(' . \implode(' or ', $sql2Where) . ')';
        }

        return '';
    }

    /**
     * build categories where clauses.
     */
    protected function buildCategoriesWhere($categories, $operator, $languageCode)
    {
        $structure = $this->structureManager->getStructure('excerpt');

        $sql2Where = [];
        if ($structure->hasProperty('categories')) {
            $property = new TranslatedProperty(
                $structure->getProperty('categories'),
                $languageCode,
                $this->languageNamespace,
                'excerpt'
            );
            foreach ($categories as $category) {
                $sql2Where[] = 'page.[' . $property->getName() . '] = ' . $category;
            }

            if (\count($sql2Where) > 0) {
                return '(' . \implode(' ' . \strtoupper($operator) . ' ', $sql2Where) . ')';
            }
        }

        return '';
    }

    /**
     * checks if config has given config name.
     *
     * @param string $name config name
     *
     * @return bool
     */
    protected function hasConfig($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * returns config value.
     *
     * @param string $name config name
     *
     * @return mixed config value
     */
    protected function getConfig($name, $default = null)
    {
        if (!$this->hasConfig($name)) {
            return $default;
        }

        return $this->config[$name];
    }

    /**
     * build select for uuids.
     */
    protected function buildPageSelector()
    {
        $idsWhere = [];
        foreach ($this->ids as $id) {
            $idsWhere[] = \sprintf("page.[jcr:uuid] = '%s'", $id);
        }

        return '(' . \implode(' OR ', $idsWhere) . ')';
    }

    /**
     * build sql for exluded Pages.
     */
    private function buildPageExclude()
    {
        $idsWhere = [];
        foreach ($this->excluded as $id) {
            $idsWhere[] = \sprintf("(NOT page.[jcr:uuid] = '%s')", $id);
        }

        return $idsWhere;
    }
}
