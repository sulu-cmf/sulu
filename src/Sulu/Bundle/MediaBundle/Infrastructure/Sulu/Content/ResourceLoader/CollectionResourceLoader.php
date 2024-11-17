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

use Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\ResourceLoaderInterface;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class CollectionResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'collection';

    public function __construct(
        private CollectionManagerInterface $collectionManager,
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $mappedResult = [];
        foreach ($ids as $id) {
            try {
                $collection = $this->collectionManager->getById($id, $locale); // TODO load all over one query
                $mappedResult[$collection->getId()] = $collection;
            } catch (CollectionNotFoundException $e) {
                // @ignoreException: do not crash page if selected collection is deleted
            }
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
