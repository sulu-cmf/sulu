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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

class CustomUrl implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'custom_urls';

    private ?int $id = null;

    private string $title;

    private bool $published = false;

    private string $webspace;

    private string $baseDomain;

    /**
     * @var array<string>
     */
    private array $domainParts = [];

    private string $targetDocument;

    private string $targetLocale;

    private bool $canonical = false;

    private bool $redirect = false;

    private bool $noFollow = false;

    private bool $noIndex = false;

    /**
     * @var Collection<array-key, CustomUrlRoute>
     */
    private Collection $routes;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setWebspace(string $webspace): void
    {
        $this->webspace = $webspace;
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function setBaseDomain(string $baseDomain): void
    {
        $this->baseDomain = $baseDomain;
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    /**
     * @param array<string> $domainParts
     */
    public function setDomainParts(array $domainParts): void
    {
        $this->domainParts = $domainParts;
    }

    /**
     * @return array<string>
     */
    public function getDomainParts(): array
    {
        return $this->domainParts;
    }

    public function setTargetDocument(string $targetDocument): void
    {
        $this->targetDocument = $targetDocument;
    }

    public function getTargetDocument(): string
    {
        return $this->targetDocument;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function setTargetLocale(string $targetLocale): void
    {
        $this->targetLocale = $targetLocale;
    }

    public function isCanonical(): bool
    {
        return $this->canonical;
    }

    public function setCanonical(bool $canonical): void
    {
        $this->canonical = $canonical;
    }

    public function isRedirect(): bool
    {
        return $this->redirect;
    }

    public function setRedirect(bool $redirect): void
    {
        $this->redirect = $redirect;
    }

    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    public function setNoFollow(bool $noFollow): void
    {
        $this->noFollow = $noFollow;
    }

    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): void
    {
        $this->noIndex = $noIndex;
    }

    /**
     * @return Collection<array-key, CustomUrlRoute>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(CustomUrlRoute $route): void
    {
        $this->routes[] = $route;
    }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}
