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
 *      authored: \DateTime|null,
 *      shadowBaseLocale: string|null,
 *      lastModified?: \DateTimeImmutable|null
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
        /** @var SettingsData $result */
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
            $result = \array_merge($result, $this->getLastModifiedData($dimensionContent));
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

        if (!isset($templateData['url'])) {
            return [
                'localizations' => [],
            ];
        }

        /** @var string $templateUrl */
        $templateUrl = $templateData['url'];
        $webspaceKey = $dimensionContent instanceof WebspaceInterface ? $dimensionContent->getMainWebspace() : null;

        $localizations = $this->localizationManager->getLocalizations();
        $localizationData = [];

        $availableLocales = $dimensionContent->getAvailableLocales() ?? [];
        $availableLocales = \array_combine($availableLocales, $availableLocales);
        foreach ($localizations as $locale => $localization) {
            $url = isset($availableLocales[$locale]) ? $templateUrl : '/';

            $resolvedUrl = $this->webspaceManager->findUrlByResourceLocator(
                $url,
                null,
                $locale,
                $webspaceKey
            );

            $localizationData[$locale] = [
                'locale' => $locale,
                'url' => $resolvedUrl,
                'country' => $localization->getCountry(),
                'alternate' => '/' !== $url, // true for alternative locales
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

    /**
     * @return array{
     *     lastModified?: \DateTimeImmutable|null
     * }
     */
    protected function getLastModifiedData(AuthorInterface $dimensionContent): array
    {
        if (!$dimensionContent->getLastModifiedEnabled()) {
            return [];
        }

        return [
            'lastModified' => $dimensionContent->getLastModified(),
        ];
    }
}
