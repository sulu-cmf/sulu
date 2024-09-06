<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Generator;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Url\ReplacerInterface;

/**
 * Generates urls for custom-urls.
 */
class Generator implements GeneratorInterface
{
    public const PREFIX_REGEX = '/^([^\/]*)(\*)(.*)$/';

    public const POSTFIX_REGEX = '/^.*\/.*\*.*$/';

    public function __construct(private ReplacerInterface $urlReplacer)
    {
    }

    public function generate(string $baseDomain, array $domainParts, ?Localization $locale = null): string
    {
        $domain = $baseDomain;

        foreach ($domainParts as $domainPart) {
            /** @var string $domain */
            $domain = \preg_replace('/\*/', $domainPart, $domain, 1);
        }

        if (\str_contains($domain, '*')) {
            throw new MissingDomainPartException($baseDomain, $domainParts, $domain);
        }

        if ($locale) {
            $domain = $this->localizeDomain($domain, $locale);
        }

        return \rtrim($domain, '/');
    }

    /**
     * Localize given domain.
     *
     * @param string $domain
     *
     * @return string
     */
    protected function localizeDomain($domain, Localization $locale)
    {
        if (!$this->urlReplacer->hasLocalizationReplacer($domain)
            && !$this->urlReplacer->hasLanguageReplacer($domain)
        ) {
            $domain = $this->urlReplacer->appendLocalizationReplacer($domain);
        }

        $domain = $this->urlReplacer->replaceLanguage($domain, $locale->getLanguage());
        $domain = $this->urlReplacer->replaceCountry($domain, $locale->getCountry());
        $domain = $this->urlReplacer->replaceLocalization($domain, $locale->getLocale());

        return $this->urlReplacer->cleanup($domain);
    }
}
