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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class MediaResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'media';

    public function __construct(
        private MediaManagerInterface $mediaManager
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->mediaManager->getByIds($ids, (string) $locale);

        $mappedResult = [];
        foreach ($result as $media) {
            $mappedResult[$media->getId()] = $media;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
