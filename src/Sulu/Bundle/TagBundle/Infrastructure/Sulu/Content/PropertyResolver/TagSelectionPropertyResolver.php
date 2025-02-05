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

namespace Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\ResourceLoader\TagResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class TagSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)
            || 0 === \count($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], ['ids' => [], ...$params]);
        }

        /** @var string[] $names */
        $names = $data;

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? TagResourceLoader::getKey();

        return ContentView::createResolvables(
            $names,
            $resourceLoaderKey,
            ['ids' => $names, ...$params],
        );
    }

    public static function getType(): string
    {
        return 'tag_selection';
    }
}
