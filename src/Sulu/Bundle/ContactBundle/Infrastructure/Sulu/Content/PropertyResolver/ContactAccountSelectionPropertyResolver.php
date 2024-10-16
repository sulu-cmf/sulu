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
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\ContactResourceLoader;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\PropertyResolverInterface;

class ContactAccountSelectionPropertyResolver implements PropertyResolverInterface
{
    public const PREFIX_CONTACT = 'c';

    public const PREFIX_ACCOUNT = 'a';

    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)
            || 0 === \count($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], ['ids' => [], ...$params]);
        }

        /** @var string $contactResourceLoaderKey */
        $contactResourceLoaderKey = $params['contactResourceLoader'] ?? ContactResourceLoader::getKey();
        /** @var string $contactResourceLoaderKey */
        $accountResourceLoaderKey = $params['accountResourceLoader'] ?? AccountResourceLoader::getKey();

        $resolvableResources = [];
        foreach ($data as $id) {
            if (!\is_string($id)) {
                continue;
            }

            $key = \substr($id, 0, 1);
            $id = \substr($id, 1);
            $id = \is_numeric($id) ? \intval($id) : null;

            if (null === $id) { // ignore invalid ids, invalid value can happen when template or block type was changed
                continue;
            }

            // this is a very edge case normally the `ResolvableResource` class should not be used by property resolvers
            // but in this case we need to use it to load resources depending on the key correctly
            // the ResolvableResource is kept internal to the content bundle and should not be used by other bundles
            match ($key) {
                self::PREFIX_CONTACT => $resolvableResources[] = new ResolvableResource($id, $contactResourceLoaderKey),
                self::PREFIX_ACCOUNT => $resolvableResources[] = new ResolvableResource($id, $accountResourceLoaderKey),
                default => null,
            };
        }

        return ContentView::create(
            $resolvableResources,
            [
                'ids' => 0 === \count($resolvableResources) ? [] : $data,
                ...$params,
            ],
        );
    }

    public static function getType(): string
    {
        return 'contact_account_selection';
    }
}
