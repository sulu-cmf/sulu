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

namespace Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\ResourceLoader\CategoryResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SingleCategorySelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_int($data)) {
            return ContentView::create([], $params);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? CategoryResourceLoader::getKey();

        return ContentView::createResolvable(
            $data,
            $resourceLoaderKey,
            $params,
        );
    }

    public static function getType(): string
    {
        return 'single_category_selection';
    }
}
