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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Model;

trait DimensionContentTrait
{
    /**
     * @var string|null
     */
    protected $locale;

    /**
     * @var string|null
     */
    protected $ghostLocale;

    /**
     * @var string[]|null
     */
    protected $availableLocales;

    /**
     * @var string
     */
    protected $stage = DimensionContentInterface::STAGE_DRAFT;

    /**
     * @var bool
     */
    private $isMerged = false;

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function setGhostLocale(?string $ghostLocale): void
    {
        $this->ghostLocale = $ghostLocale;
    }

    public function getGhostLocale(): ?string
    {
        return $this->ghostLocale;
    }

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function addAvailableLocale(string $availableLocale): void
    {
        if (null === $this->availableLocales) {
            $this->availableLocales = [];
        }

        if (!\in_array($availableLocale, $this->availableLocales, true)) {
            $this->availableLocales[] = $availableLocale;
        }
    }

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function removeAvailableLocale(string $availableLocale): void
    {
        if (null === $this->availableLocales) {
            return;
        }

        $removeIndex = \array_search($availableLocale, $this->availableLocales, true);
        if (false !== $removeIndex) {
            unset($this->availableLocales[$removeIndex]);
            $this->availableLocales = \array_values($this->availableLocales);
        }
    }

    public function getAvailableLocales(): ?array
    {
        return $this->availableLocales;
    }

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function isMerged(): bool
    {
        return $this->isMerged;
    }

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function markAsMerged(): void
    {
        $this->isMerged = true;
    }

    public static function getDefaultDimensionAttributes(): array
    {
        return [
            'locale' => null,
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];
    }

    public static function getEffectiveDimensionAttributes(array $dimensionAttributes): array
    {
        $defaultValues = static::getDefaultDimensionAttributes();

        // Ignore keys that are not part of the default attributes
        $dimensionAttributes = \array_intersect_key($dimensionAttributes, $defaultValues);

        $dimensionAttributes = \array_merge(
            $defaultValues,
            $dimensionAttributes
        );

        return $dimensionAttributes;
    }
}
