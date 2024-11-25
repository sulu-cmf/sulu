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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\CollectionResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SingleCollectionSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_numeric($data)) {
            return ContentView::create(null, ['id' => null, ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? CollectionResourceLoader::getKey();

        return ContentView::createResolvable(
            (int) $data,
            $resourceLoaderKey,
            [
                'id' => $data,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'single_collection_selection';
    }
}
