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

namespace Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\Loader;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;

class LinkResourceLoader implements ResourceLoaderInterface
{
    public const LINK_TYPE_EXTERNAL = 'external';
    public const RESOURCE_LOADER_KEY = 'link';

    public function __construct(
        private LinkProviderPoolInterface $linkProviderPool
    ) {
    }

    /**
     * @param string[] $ids
     * @param mixed[] $params
     *
     * @return array<string, mixed>
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        if ([] === $ids) {
            return [];
        }

        $links = [];
        foreach ($this->getIdsByProvider($ids) as $provider => $ids) {
            $links = \array_merge($links, $this->loadLinks($provider, $ids, $locale));
        }

        return $links;
    }

    /**
     * @param string[] $ids
     *
     * @return array<string, string|LinkItem>
     */
    private function loadLinks(string $provider, array $ids, ?string $locale): array
    {
        if (self::LINK_TYPE_EXTERNAL === $provider) {
            return \array_combine($ids, $ids);
        }

        if (null === $locale) {
            throw new \RuntimeException('Locale is required to resolve non external Links');
        }

        $linkProvider = $this->linkProviderPool->getProvider($provider);

        $links = [];
        foreach ($linkProvider->preload($ids, $locale) as $link) {
            $links[$provider . '::' . $link->getId()] = $link;
        }

        return $links;
    }

    /**
     * @param string[] $ids
     *
     * @return array<string, string[]>
     */
    private function getIdsByProvider(array $ids): array
    {
        $idsByProvider = [];
        foreach ($ids as $id) {
            [$provider, $id] = \explode('::', $id, 2);
            $idsByProvider[$provider][] = $id;
        }

        return $idsByProvider;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
