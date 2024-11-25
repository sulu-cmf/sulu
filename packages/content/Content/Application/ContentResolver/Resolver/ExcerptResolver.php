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
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class ExcerptResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent): ContentView
    {
        if (!$dimensionContent instanceof ExcerptInterface) {
            throw new \RuntimeException('DimensionContent needs to extend the ' . ExcerptInterface::class);
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata($this->getFormKey(), $locale, []);

        return ContentView::create(
            $this->metadataResolver->resolveItems(
                $formMetadata->getItems(),
                $this->getExcerptData($dimensionContent),
                $locale
            ),
            []
        );
    }

    protected function getFormKey(): string
    {
        return 'content_excerpt';
    }

    /**
     * @return array{
     *     excerptTitle: string|null,
     *     excerptMore: string|null,
     *     excerptDescription: string|null,
     *     excerptCategories: int[],
     *     excerptTags: string[],
     *     excerptIcon: array{id: int}|null,
     *     excerptImage: array{id: int}|null
     * }
     */
    protected function getExcerptData(ExcerptInterface $dimensionContent): array
    {
        return [
            'excerptTitle' => $dimensionContent->getExcerptTitle(),
            'excerptMore' => $dimensionContent->getExcerptMore(),
            'excerptDescription' => $dimensionContent->getExcerptDescription(),
            'excerptCategories' => \array_map(
                fn (CategoryInterface $category) => $category->getId(),
                $dimensionContent->getExcerptCategories()
            ),
            'excerptTags' => \array_map(
                fn (TagInterface $tag) => $tag->getName(), $dimensionContent->getExcerptTags()
            ),
            'excerptIcon' => $dimensionContent->getExcerptIcon(),
            'excerptImage' => $dimensionContent->getExcerptImage(),
        ];
    }
}
