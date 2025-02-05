<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Manager;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Provider\LocalizationProviderInterface;

/**
 * Interface for the management of localizations, the implementing class will manage the localizations based
 * on LocalizationProviders, which can be registered via the DIC.
 */
interface LocalizationManagerInterface
{
    /**
     * @return array<string, Localization>
     */
    public function getLocalizations(): array;

    /**
     * @return string[]
     */
    public function getLocales(): array;

    public function addLocalizationProvider(LocalizationProviderInterface $localizationProvider);
}
