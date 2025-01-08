<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\Message;

use Webmozart\Assert\Assert;

/**
 * @experimental
 */
class CreateArticleMessage
{
    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @var string|null
     */
    private $uuid;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $uuid = $data['uuid'] ?? null;

        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
        Assert::nullOrString($uuid, 'Expected "uuid" to be a string.');

        $this->data = $data;
        $this->uuid = $uuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
