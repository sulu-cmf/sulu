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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;

/**
 * @phpstan-type SettingsData array{
 *      availableLocales: string[]|null,
 *      localizations: array<string, array{
 *          locale: string,
 *          url: string|null,
 *          country: string,
 *          alternate: bool
 *      }>,
 *      mainWebspace: string|null,
 *      template: string|null,
 *      author: int|null,
 *      authored: \DateTimeInterface|null,
 *      shadowBaseLocale: string|null
 *  }
 */
readonly class SettingsResolver implements ResolverInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private LocalizationManagerInterface $localizationManager
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent): ContentView
    {
        // TODO add last modified data

        /**
         * @var SettingsData $result
         */
        $result = [
            'availableLocales' => $dimensionContent->getAvailableLocales() ?? [],
        ];

        if ($dimensionContent instanceof RoutableInterface && $dimensionContent instanceof TemplateInterface) {
            $result = \array_merge($result, $this->getLocalizationsData($dimensionContent));
        }

        if ($dimensionContent instanceof WebspaceInterface) {
            $result = \array_merge($result, $this->getWebspaceData($dimensionContent));
        }

        if ($dimensionContent instanceof TemplateInterface) {
            $result = \array_merge($result, $this->getTemplateData($dimensionContent));
        }

        if ($dimensionContent instanceof AuthorInterface) {
            $result = \array_merge($result, $this->getAuthorData($dimensionContent));
        }

        if ($dimensionContent instanceof ShadowInterface) {
            $result = \array_merge($result, $this->getShadowData($dimensionContent));
        }

        return ContentView::create($result, []);
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param RoutableInterface&TemplateInterface&DimensionContentInterface<T> $dimensionContent
     *
     * @return array{
     *     localizations: array<string, array{
     *         locale: string,
     *         url: string|null,
     *         country: string,
     *         alternate: bool
     *      }>
     * }
     */
    protected function getLocalizationsData(RoutableInterface&TemplateInterface&DimensionContentInterface $dimensionContent): array
    {
        $templateData = $dimensionContent->getTemplateData();
        /** @var string|null $url */
        $url = $templateData['url'] ?? null;
        $webspaceKey = $dimensionContent instanceof WebspaceInterface ? $dimensionContent->getMainWebspace() : null;

        $localizations = $this->localizationManager->getLocalizations();
        $localizationData = [];

        $availableLocales = $dimensionContent->getAvailableLocales() ?? [];
        foreach ($availableLocales as $locale) {
            /** @var Localization $localization */
            $localization = $localizations[$locale];

            $resolvedUrl = $this->webspaceManager->findUrlByResourceLocator(
                (string) $url,
                null,
                $localization->getLocale(),
                $webspaceKey
            );

            $localizationData[$locale] = [
                'locale' => $locale,
                'url' => $resolvedUrl,
                'country' => $localization->getCountry(),
                'alternate' => null !== $resolvedUrl, // TODO test page that has only one locale
            ];
        }

        return [
            'localizations' => $localizationData,
        ];
    }

    /**
     * @return array{
     *     mainWebspace: string|null
     * }
     */
    protected function getWebspaceData(WebspaceInterface $dimensionContent): array
    {
        return [
            'mainWebspace' => $dimensionContent->getMainWebspace(),
        ];
    }

    /**
     * @return array{
     *     template: string|null
     * }
     */
    protected function getTemplateData(TemplateInterface $dimensionContent): array
    {
        return [
            'template' => $dimensionContent->getTemplateKey(),
        ];
    }

    /**
     * @return array{
     *     author: int|null,
     *     authored: \DateTimeInterface|null
     * }
     */
    protected function getAuthorData(AuthorInterface $dimensionContent): array
    {
        return [
            'author' => $dimensionContent->getAuthor()?->getId(),
            'authored' => $dimensionContent->getAuthored(),
        ];
    }

    /**
     * @return array{
     *     shadowBaseLocale: string|null
     * }
     */
    protected function getShadowData(ShadowInterface $dimensionContent): array
    {
        return [
            'shadowBaseLocale' => $dimensionContent->getShadowLocale(),
        ];
    }
}
