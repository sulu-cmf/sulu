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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Mocks;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

/**
 * Trait for composing a class that wraps a DimensionContentInterface mock.
 *
 * @see MockWrapper to learn why this trait is needed.
 *
 * @property DimensionContentInterface $instance
 */
trait DimensionContentMockWrapperTrait
{
    public static function getResourceKey(): string
    {
        return 'mock-resource-key';
    }

    public function getLocale(): ?string
    {
        return $this->instance->getLocale();
    }

    public function setLocale(?string $locale): void
    {
        $this->instance->setLocale($locale);
    }

    public function getGhostLocale(): ?string
    {
        return $this->instance->getGhostLocale();
    }

    public function setGhostLocale(?string $ghostLocale): void
    {
        $this->instance->setGhostLocale($ghostLocale);
    }

    public function getAvailableLocales(): ?array
    {
        return $this->instance->getAvailableLocales();
    }

    public function removeAvailableLocale(string $availableLocale): void
    {
        $this->instance->removeAvailableLocale();
    }

    public function addAvailableLocale(string $availableLocale): void
    {
        $this->instance->addAvailableLocale($availableLocale);
    }

    public function getStage(): string
    {
        return $this->instance->getStage();
    }

    public function setStage(string $stage): void
    {
        $this->instance->setStage($stage);
    }

    public function getResource(): ContentRichEntityInterface
    {
        return $this->instance->getResource();
    }

    public function isMerged(): bool
    {
        return $this->instance->isMerged();
    }

    public function markAsMerged(): void
    {
        $this->instance->markAsMerged();
    }

    public static function getDefaultDimensionAttributes(): array
    {
        return [
            'locale' => null,
            'stage' => 'draft',
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
