<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class CategoryResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'category';

    public function __construct(
        private CategoryManagerInterface $categoryManager
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->categoryManager->findByIds($ids);

        $mappedResult = [];
        foreach ($result as $category) {
            $mappedResult[$category->getId()] = $category;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
