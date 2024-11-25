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
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class SingleMediaSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $displayOption = (\is_array($data) && isset($data['displayOption']) && \is_string($data['displayOption']))
            ? $data['displayOption']
            : null;

        if (!\is_array($data)
            || !isset($data['id'])
            || !\is_numeric($data['id'])
        ) {
            return ContentView::create(null, ['id' => null, 'displayOption' => $displayOption, ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? MediaResourceLoader::getKey();
        $id = (int) $data['id'];

        return ContentView::createResolvable(
            $id,
            $resourceLoaderKey,
            [
                'id' => $id,
                'displayOption' => $displayOption,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'single_media_selection';
    }
}
