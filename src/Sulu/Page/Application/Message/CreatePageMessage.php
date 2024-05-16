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

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @experimental
 */
class CreatePageMessage
{
    private array $data;

    private string $uuid;

    public function __construct(private string $webspaceKey, private string $parentId, array $data)
    {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
        $this->data = $data;

        $this->uuid = Uuid::v7()->toRfc4122();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
