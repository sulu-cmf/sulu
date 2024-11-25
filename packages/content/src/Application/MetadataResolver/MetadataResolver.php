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

namespace Sulu\Content\Application\MetadataResolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;

/**
 * @internal This class is intended for internal use only within the library. Modifying or depending on this class may result in unexpected behavior and is not supported.
 */
class MetadataResolver
{
    public function __construct(
        private PropertyResolverProvider $propertyResolverProvider
    ) {
    }

    /**
     * @param ItemMetadata[] $items
     * @param mixed[] $data
     *
     * @return ContentView[]
     */
    public function resolveItems(array $items, array $data, string $locale): array
    {
        $contentViews = [];
        foreach ($items as $item) {
            $name = $item->getName();
            $type = $item->getType();
            if ($item instanceof SectionMetadata) {
                $contentViews = \array_merge(
                    $contentViews,
                    $this->resolveItems($item->getItems(), $data, $locale)
                );
            } else {
                $contentViews[$name] = $this->resolveProperty($type, $data[$name] ?? null, $locale, ['metadata' => $item]);
            }
        }

        return $contentViews;
    }

    /**
     * @param mixed[] $params
     */
    private function resolveProperty(string $type, mixed $data, string $locale, array $params = []): ContentView
    {
        $propertyResolver = $this->propertyResolverProvider->getPropertyResolver($type);

        return $propertyResolver->resolve($data, $locale, $params);
    }
}
