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

namespace Sulu\Bundle\CustomUrlBundle\Entity;

use Sulu\Component\Persistence\Model\TimestampableInterface;
use Sulu\Component\Persistence\Model\TimestampableTrait;

class CustomUrlRoute implements TimestampableInterface
{
    use TimestampableTrait;

    private ?int $id;

    public function __construct(
        private CustomUrl $customUrl,
        private string $path
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setCustomUrl(CustomUrl $customUrl): void
    {
        $this->customUrl = $customUrl;
    }

    public function getCustomUrl(): CustomUrl
    {
        return $this->customUrl;
    }
}
