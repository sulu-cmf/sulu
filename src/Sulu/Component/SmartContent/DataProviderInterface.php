<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;

/**
 * Interface for DataProviders which will be usable in SmartContent component.
 */
interface DataProviderInterface
{
    /**
     * Returns configuration for smart-content.
     *
     * @return ProviderConfigurationInterface
     */
    public function getConfiguration();

    /**
     * Returns default parameter.
     *
     * @return PropertyParameter[]
     */
    public function getDefaultPropertyParameter();

    /**
     * Resolves given filters and returns filtered data items.
     *
     * @param array{
     *     dataSource?: string|int|null,
     *     sortMethod?: 'asc'|'desc',
     *     sortBy?: string,
     *     tags?: string[],
     *     tagOperator?: 'or'|'and',
     *     types?: string[],
     *     categories?: int[],
     *     categoryOperator?: 'or'|'and',
     *     targetGroupId?: string|int|null,
     *     websiteTags?: string[],
     *     websiteTagsOperator?: 'or'|'and',
     *     websiteCategories?: int[],
     *     websiteCategoriesOperator?: 'or'|'and',
     *     limitResult?: int,
     * } $filters Contains the filter configuration
     * @param array<string, PropertyParameter> $propertyParameter Contains the parameter of resolved property
     * @param mixed[] $options Options like webspace or locale
     * @param int|null $limit Indicates maximum size of result set
     * @param int $page Indicates page of result set
     * @param int|null $pageSize Indicates page-size of result set
     *
     * @return DataProviderResult
     */
    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    );

    /**
     * Resolves given filters and returns filtered resource items with ArrayAccess.
     *
     * @param array{
     *      dataSource?: string|int|null,
     *      sortMethod?: 'asc'|'desc',
     *      sortBy?: string,
     *      tags?: string[],
     *      tagOperator?: 'or'|'and',
     *      types?: string[],
     *      categories?: int[],
     *      categoryOperator?: 'or'|'and',
     *      targetGroupId?: string|int|null,
     *      websiteTags?: string[],
     *      websiteTagsOperator?: 'or'|'and',
     *      websiteCategories?: int[],
     *      websiteCategoriesOperator?: 'or'|'and',
     *      limitResult?: int,
     *  } $filters Contains the filter configuration
     * @param array<string, PropertyParameter> $propertyParameter Contains the parameter of resolved property
     * @param mixed[] $options Options like webspace or locale
     * @param int|null $limit Indicates maximum size of result set
     * @param int $page Indicates page of result set
     * @param int|null $pageSize Indicates page-size of result set
     *
     * @return DataProviderResult
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    );

    /**
     * Resolves datasource and returns the data of it.
     *
     * @param string|int|null $datasource Identification of datasource
     * @param PropertyParameter[] $propertyParameter Contains the parameter of resolved property
     * @param mixed[] $options Options like webspace or locale
     *
     * @return DatasourceItemInterface|null
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options);
}
