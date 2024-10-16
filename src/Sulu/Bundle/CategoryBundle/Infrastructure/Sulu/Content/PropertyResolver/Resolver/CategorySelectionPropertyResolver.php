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

namespace Sulu\Bundle\CategoryBundle\Infrastructure\Content\PropertyResolver\Resolver;

use Sulu\Bundle\CategoryBundle\Infrastructure\Content\ResourceLoader\CategoryResourceLoader;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\PropertyResolverInterface;

class CategorySelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (empty($data) || !\is_array($data) || !isset($data['ids'])) {
            return ContentView::create([], ['ids' => []]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? CategoryResourceLoader::getKey();

        return ContentView::createResolvables(
            $data['ids'],
            $resourceLoaderKey,
            ['ids' => $data['ids']],
        );
    }

    public static function getType(): string
    {
        return 'category_selection';
    }
}
