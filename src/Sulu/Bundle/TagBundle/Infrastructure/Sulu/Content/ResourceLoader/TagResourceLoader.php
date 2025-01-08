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

namespace Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class TagResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'tag';

    public function __construct(
        private TagRepositoryInterface $tagRepository
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->tagRepository->findBy(['name' => $ids]);

        $mappedResult = [];
        foreach ($result as $tag) {
            $mappedResult[$tag->getName()] = $tag->getName();
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
