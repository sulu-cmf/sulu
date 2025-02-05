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

namespace Sulu\Content\Domain\Model;

use Sulu\Bundle\ContactBundle\Entity\ContactInterface;

/**
 * Basic implementation of the AuthorInterface.
 */
trait AuthorTrait
{
    /**
     * @var ContactInterface|null
     */
    private $author;

    /**
     * @var \DateTimeImmutable|null
     */
    private $authored;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastModified;

    public function getAuthor(): ?ContactInterface
    {
        return $this->author;
    }

    public function setAuthor(?ContactInterface $author): void
    {
        $this->author = $author;
    }

    public function getAuthored(): ?\DateTimeImmutable
    {
        return $this->authored;
    }

    public function setAuthored(?\DateTimeImmutable $authored): void
    {
        $this->authored = $authored;
    }

    public function getLastModifiedEnabled(): ?bool
    {
        return null !== $this->lastModified;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function setLastModified(?\DateTimeImmutable $lastModified): void
    {
        $this->lastModified = $lastModified;
    }
}
