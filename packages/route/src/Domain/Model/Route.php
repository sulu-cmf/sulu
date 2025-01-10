<?php

namespace Sulu\Route\Domain\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\UniqueConstraint(name: 'route_unique', fields: ['site', 'locale', 'slug'])]
#[ORM\UniqueConstraint(name: 'resource_unique', fields: ['site', 'locale', 'resourceKey', 'resourceId'])]
class Route
{
    private ?int $id = null;

    private ?string $site;

    private ?string $locale;

    private ?string $slug;

    private ?Route $parentRoute;

    private ?string $resourceKey;

    private ?string $resourceId;

    public function __construct(string $resourceKey, string $resourceId, string $locale, string $slug, ?string $site = null, ?Route $parentRoute = null)
    {
        $this->resourceKey = $resourceKey;
        $this->resourceId = $resourceId;
        $this->locale = $locale;
        $this->slug = $slug;
        $this->site = $site;
        $this->parentRoute = $parentRoute;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function setParentRoute(?Route $parentRoute): void
    {
        $this->parentRoute = $parentRoute;
    }

    public function getParentRoute(): ?Route
    {
        return $this->parentRoute;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getResourceKey(): ?string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }
}
