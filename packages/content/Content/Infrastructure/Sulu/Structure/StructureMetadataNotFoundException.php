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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure;

class StructureMetadataNotFoundException extends \Exception
{
    public function __construct(string $templateType, ?string $templateKey)
    {
        parent::__construct(\sprintf(
            'No structure metadata found for template type "%s" and template key "%s"',
            $templateType,
            $templateKey
        ));
    }
}
