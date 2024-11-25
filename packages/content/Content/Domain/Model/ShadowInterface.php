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

interface ShadowInterface
{
    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function setShadowLocale(?string $shadowLocale): void;

    public function getShadowLocale(): ?string;

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function addShadowLocale(string $locale, string $shadowLocale): void;

    /**
     * @internal should only be set by content bundle services not from outside
     */
    public function removeShadowLocale(string $locale): void;

    /**
     * Returns the locales which shadow the given locale.
     *
     * @return array<string, string>|null
     */
    public function getShadowLocales(): ?array;

    /**
     * @internal should only be set by content bundle services not from outside
     *
     * Returns the locales which shadow the given locale
     *
     * @return string[]
     */
    public function getShadowLocalesForLocale(string $shadowLocale): array;
}
