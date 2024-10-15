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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\ResourceLoaderInterface;

class ContactResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'contact';

    public function __construct(
        private ContactManagerInterface $contactManager,
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->contactManager->getByIds($ids, (string) $locale);

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
