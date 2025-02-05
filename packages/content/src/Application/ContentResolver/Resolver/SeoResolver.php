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

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;

readonly class SeoResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent): ContentView
    {
        if (!$dimensionContent instanceof SeoInterface) {
            throw new \RuntimeException('DimensionContent needs to extend the ' . SeoInterface::class);
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata($this->getFormKey(), $locale, []);

        $items = \array_filter($formMetadata->getItems(), function($item) {
            return !\in_array($item->getType(), $this->excludedPropertyTypes(), true);
        });
        $resolvedItems = $this->metadataResolver->resolveItems($items, $this->getSeoData($dimensionContent), $locale);

        return ContentView::create($this->normalizeResolvedItems($resolvedItems), []);
    }

    /**
     * @param mixed[] $resolvedItems
     *
     * @return mixed[]
     */
    protected function normalizeResolvedItems(array $resolvedItems): array
    {
        $result = [];
        foreach ($resolvedItems as $key => $item) {
            $normalizedKey = \lcfirst(\substr((string) $key, \strlen('seo')));
            $result[$normalizedKey] = $item;
        }

        return $result;
    }

    protected function getFormKey(): string
    {
        return 'content_seo';
    }

    /**
     * @return string[]
     */
    protected function excludedPropertyTypes(): array
    {
        return ['search_result'];
    }

    /**
     * @return array{
     *     seoTitle: string|null,
     *     seoDescription: string|null,
     *     seoKeywords: string|null,
     *     seoCanonicalUrl: string|null,
     *     seoNoIndex: bool,
     *     seoNoFollow: bool,
     *     seoHideInSitemap: bool
     * }
     */
    protected function getSeoData(SeoInterface $dimensionContent): array
    {
        return [
            'seoTitle' => $dimensionContent->getSeoTitle(),
            'seoDescription' => $dimensionContent->getSeoDescription(),
            'seoKeywords' => $dimensionContent->getSeoKeywords(),
            'seoCanonicalUrl' => $dimensionContent->getSeoCanonicalUrl(),
            'seoNoIndex' => $dimensionContent->getSeoNoIndex(),
            'seoNoFollow' => $dimensionContent->getSeoNoFollow(),
            'seoHideInSitemap' => $dimensionContent->getSeoHideInSitemap(),
        ];
    }
}
