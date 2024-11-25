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
use Sulu\Content\Application\ResourceLoader\Loader\LinkResourceLoader;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;

class LinkPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param array{
     *    rel?: string,
     *    href: string,
     *    query?: string,
     *    title?: string,
     *    anchor?: string,
     *    target?: string,
     *    provider?: string
     * }|mixed $data
     * @param mixed[] $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (
            !\is_array($data)
            || !\array_key_exists('href', $data)
            || !\array_key_exists('provider', $data)
        ) {
            return ContentView::create($data, [...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? LinkResourceLoader::getKey();

        return ContentView::createResolvable(
            $data['provider'] . '::' . $data['href'],
            $resourceLoaderKey,
            [
                ...$data,
                ...$params,
            ],
            // external links are not passed as a LinkItem
            static function(string|LinkItem $linkItem) use ($data) {
                if (\is_string($linkItem)) {
                    return $linkItem;
                }

                $url = $linkItem->getUrl();
                if (isset($data['query'])) {
                    $url = \sprintf('%s?%s', $url, \ltrim($data['query'], '?'));
                }
                if (isset($data['anchor'])) {
                    $url = \sprintf('%s#%s', $url, \ltrim($data['anchor'], '#'));
                }

                return $url;
            }
        );
    }

    public static function getType(): string
    {
        return 'link';
    }
}
