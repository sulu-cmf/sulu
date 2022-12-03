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
     * @var array{
     *     uuid?: string
     * }
     */
    private $identifier;

    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     * @param mixed[] $data
     */
    public function __construct(array $identifier, array $data)
    {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');

        $this->identifier = $identifier;
        $this->data = $data;
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
