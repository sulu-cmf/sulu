<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SnippetSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (
            !\is_array($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], \array_merge(['ids' => []], $params));
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? SnippetResourceLoader::getKey();

        return ContentView::createResolvables(
            $data,
            $resourceLoaderKey,
            [
                'ids' => $data,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'snippet_selection';
    }
}
