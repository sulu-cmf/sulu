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
class ModifyArticleMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     * @param mixed[] $data
     */
    public function __construct(private array $identifier, private array $data)
    {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
