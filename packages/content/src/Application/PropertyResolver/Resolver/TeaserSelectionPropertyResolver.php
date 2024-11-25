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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ResourceLoader\Loader\TeaserResourceLoader;
use Sulu\Bundle\PageBundle\Teaser\Teaser;

class TeaserSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param mixed[] $data
     * @param mixed[] $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $returnedParams = ['presentsAs' => $data['presentsAs'] ?? null, ...$params];
        unset($returnedParams['metadata']);

        if (
            !\is_array($data)
            || !\array_key_exists('items', $data)
            || !\is_array($data['items'])
        ) {
            return ContentView::create($data, $returnedParams);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? TeaserResourceLoader::getKey();

        $contentViews = [];
        foreach ($data['items'] as $item) {
            if (!\is_array($item) || !\array_key_exists('id', $item) || !\array_key_exists('type', $item)) {
                continue;
            }
            $type = $item['type'];
            $id = $item['id'];

            $contentViews[] = ContentView::createResolvable(
                $type . '::' . $id,
                $resourceLoaderKey,
                [
                    'id' => $id,
                    'type' => $type,
                ],
                static function(Teaser $resource) use ($item) {
                    return $resource->merge($item);
                }
            );
        }

        return ContentView::create($contentViews, $returnedParams);
    }

    public static function getType(): string
    {
        return 'teaser_selection';
    }
}
