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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;

class DatePropertyResolver implements PropertyResolverInterface
{
    public const FORMAT = 'Y-m-d';

    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (null != $data) {
            $data = \DateTime::createFromFormat(static::FORMAT, $data);
        }

        return ContentView::create($data, []);
    }

    public static function getType(): string
    {
        return 'date';
    }
}
