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

namespace Sulu\Bundle\TrashBundle\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemTranslationNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;

#[ExclusionPolicy('all')]
class TrashItem implements TrashItemInterface
{
    /**
     * @var int
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $id;

    /**
     * @var string
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $resourceKey;

    /**
     * @var string
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $resourceId;

    /**
     * @var mixed[]
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $restoreData = [];

    /**
     * The restoreType can be used to indicate a sub entity.
     *     e.g.: Store and Restore a single translation of a page.
     *          -> "translation".
     *
     * @var string|null
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $restoreType;

    /**
     * The restoreOptions are used to change behaviour of store and restore handler.
     *     e.g.: Store and Restore a single translation of a page.
     *          -> ["locale" => "en"].
     *
     * @var mixed[]
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $restoreOptions = [];

    /**
     * @var string|null
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $resourceSecurityContext;

    /**
     * @var string|null
     */
    #[Expose]
    private $resourceSecurityObjectType;

    /**
     * @var string|null
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $resourceSecurityObjectId;

    /**
     * @var \DateTimeImmutable
     */
    #[Expose]
    #[Groups(['trash_item_admin_api'])]
    private $storeTimestamp;

    /**
     * @var UserInterface|null
     */
    private $user;

    /**
     * @var Collection<int, TrashItemTranslation>
     */
    private $translations;

    /**
     * @var string|null
     */
    private $defaultLocale;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->storeTimestamp = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): TrashItemInterface
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): TrashItemInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getRestoreData(): array
    {
        return $this->restoreData;
    }

    public function setRestoreData(array $restoreData): TrashItemInterface
    {
        $this->restoreData = $restoreData;

        return $this;
    }

    public function getRestoreType(): ?string
    {
        return $this->restoreType;
    }

    public function setRestoreType(?string $restoreType): TrashItemInterface
    {
        $this->restoreType = $restoreType;

        return $this;
    }

    public function getRestoreOptions(): array
    {
        return $this->restoreOptions;
    }

    public function setRestoreOptions(array $restoreOptions): TrashItemInterface
    {
        $this->restoreOptions = $restoreOptions;

        return $this;
    }

    public function getResourceTitle(?string $locale = null): string
    {
        return $this->getTranslation($locale, true)->getTitle();
    }

    public function setResourceTitle(string $resourceTitle, ?string $locale = null): TrashItemInterface
    {
        if (!$this->hasTranslation($locale)) {
            $translation = new TrashItemTranslation($this, $locale, $resourceTitle);
            $this->addTranslation($translation);

            return $this;
        }

        $translation = $this->getTranslation($locale, false);
        $translation->setTitle($resourceTitle);

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): TrashItemInterface
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return $this->resourceSecurityObjectType;
    }

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): TrashItemInterface
    {
        $this->resourceSecurityObjectType = $resourceSecurityObjectType;

        return $this;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->resourceSecurityObjectId;
    }

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): TrashItemInterface
    {
        $this->resourceSecurityObjectId = $resourceSecurityObjectId;

        return $this;
    }

    public function getStoreTimestamp(): \DateTimeImmutable
    {
        return $this->storeTimestamp;
    }

    public function setStoreTimestamp(\DateTimeImmutable $storeTimestamp): TrashItemInterface
    {
        $this->storeTimestamp = $storeTimestamp;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    #[VirtualProperty]
    #[SerializedName('userId')]
    #[Groups(['trash_item_api'])]
    public function getUserId(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }

    public function setUser(?UserInterface $user): TrashItemInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getTranslation(?string $locale = null, bool $fallback = false): TrashItemTranslation
    {
        /** @var TrashItemTranslation|false $translation */
        $translation = $this->translations->filter(
            function(TrashItemTranslation $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first();

        if (!$translation && $fallback) {
            $translation = $this->translations->filter(
                function(TrashItemTranslation $translation) {
                    return $translation->getLocale() === $this->defaultLocale;
                }
            )->first();
        }

        if (!$translation) {
            throw new TrashItemTranslationNotFoundException($locale);
        }

        return $translation;
    }

    private function hasTranslation(?string $locale): bool
    {
        return !$this->translations->filter(
            function(TrashItemTranslation $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->isEmpty();
    }

    private function addTranslation(TrashItemTranslation $translation): void
    {
        if (0 === $this->translations->count()) {
            $this->defaultLocale = $translation->getLocale();
        }

        $this->translations->add($translation);
    }
}
