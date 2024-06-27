<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Model;

class PreviewLink implements PreviewLinkInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $visitCount = 0;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastVisit;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private string $token,
        private string $resourceKey,
        private string $resourceId,
        private string $locale,
        private array $options
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getVisitCount(): int
    {
        return $this->visitCount;
    }

    public function increaseVisitCount(): PreviewLinkInterface
    {
        ++$this->visitCount;
        $this->lastVisit = new \DateTimeImmutable();

        return $this;
    }

    public function getLastVisit(): ?\DateTimeImmutable
    {
        return $this->lastVisit;
    }

    /**
     * @param mixed[] $options
     */
    public static function create(string $token, string $resourceKey, string $resourceId, string $locale, array $options): PreviewLinkInterface
    {
        return new self(
            $token,
            $resourceKey,
            $resourceId,
            $locale,
            $options
        );
    }
}
