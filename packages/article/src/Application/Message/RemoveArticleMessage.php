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

/**
 * @experimental
 */
class RemoveArticleMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     */
    public function __construct(private array $identifier)
    {
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
}
