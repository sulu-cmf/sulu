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

namespace Sulu\Content\Application\ResourceLoader\Loader;

use Sulu\Bundle\PageBundle\Teaser\TeaserManagerInterface;

class TeaserResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'teaser';

    public function __construct(
        private TeaserManagerInterface $teaserManager
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

        if (null === $locale) {
            throw new \RuntimeException('Locale is required to resolve Teasers');
        }

        $items = $this->getItemsByProvider($ids);
        $teasers = [];
        foreach ($items as $provider => $itemsPerProvider) {
            $loadedTeasers = $this->teaserManager->find($itemsPerProvider, $locale);
            foreach ($loadedTeasers as $teaser) {
                $teasers[$provider . '::' . $teaser->getId()] = $teaser;
            }
        }

        return $teasers;
    }

    /**
     * @param string[] $ids
     *
     * @return array<string, non-empty-array<int, array{id: string, type: string}>>
     */
    private function getItemsByProvider(array $ids): array
    {
        $idsByProvider = [];
        foreach ($ids as $id) {
            [$provider, $id] = \explode('::', $id, 2);
            $idsByProvider[$provider][] = [
                'id' => $id,
                'type' => $provider,
            ];
        }

        return $idsByProvider;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
