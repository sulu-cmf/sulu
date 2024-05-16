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

use Webmozart\Assert\Assert;

/**
 * @experimental
 */
class ModifyPageMessage
{
    private array $data;

    public function __construct(private string $uuid, array $data)
    {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
        $this->data = $data;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
