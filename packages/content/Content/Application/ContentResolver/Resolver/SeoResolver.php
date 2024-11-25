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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;

class SeoResolver implements ResolverInterface
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

        return ContentView::create(
            $this->metadataResolver->resolveItems(
                $items,
                $this->getSeoData($dimensionContent),
                $locale
            ),
            []
        );
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
