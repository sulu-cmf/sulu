<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Message;

class CopyLocalePageMessage
{
    public function __construct(private string $uuid, private string $sourceLocale, private string $targetLocale)
    {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }
}
