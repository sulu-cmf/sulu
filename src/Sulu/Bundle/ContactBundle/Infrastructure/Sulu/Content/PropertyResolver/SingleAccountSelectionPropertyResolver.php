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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\AccountResourceLoader;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\PropertyResolverInterface;

class SingleAccountSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_int($data)) {
            return ContentView::create([], ['id' => null, ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? AccountResourceLoader::getKey();

        return ContentView::createResolvable(
            $data,
            $resourceLoaderKey,
            [
                'ids' => $data,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'single_account_selection';
    }
}
