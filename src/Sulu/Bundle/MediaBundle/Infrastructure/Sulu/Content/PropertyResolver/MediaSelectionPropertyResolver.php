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

use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class MediaSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $displayOption = (\is_array($data) && isset($data['displayOption']) && \is_string($data['displayOption']))
            ? $data['displayOption']
            : null;

        if (!\is_array($data)
            || !isset($data['ids'])
            || !\is_array($data['ids'])
            || 0 === \count($data['ids'])
            || !\array_is_list($data['ids'])
        ) {
            return ContentView::create([], ['ids' => [], 'displayOption' => $displayOption, ...$params]);
        }

        /** @var int[] $ids */
        $ids = $data['ids'];

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? MediaResourceLoader::getKey();

        return ContentView::createResolvables(
            $ids,
            $resourceLoaderKey,
            [
                'ids' => $ids,
                'displayOption' => $displayOption,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'media_selection';
    }
}
