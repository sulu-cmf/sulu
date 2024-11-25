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

namespace Sulu\Content\Infrastructure\Sulu\Teaser;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Infrastructure\Sulu\Traits\FindContentRichEntitiesTrait;
use Sulu\Content\Infrastructure\Sulu\Traits\ResolveContentDimensionUrlTrait;
use Sulu\Content\Infrastructure\Sulu\Traits\ResolveContentTrait;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
abstract class ContentTeaserProvider implements TeaserProviderInterface
{
    /**
     * @phpstan-use FindContentRichEntitiesTrait<T>
     */
    use FindContentRichEntitiesTrait;
    use ResolveContentDimensionUrlTrait;
    use ResolveContentTrait;

    /**
     * @var ContentManagerInterface
     */
    protected $contentManager;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ContentMetadataInspectorInterface
     */
    private $contentMetadataInspector;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var class-string<T>
     */
    protected $contentRichEntityClass;

    /**
     * @var bool
     */
    protected $showDrafts;

    /**
     * @param class-string<T> $contentRichEntityClass
     * @param bool $showDrafts Inject parameter "sulu_document_manager.show_drafts" here
     */
    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        string $contentRichEntityClass,
        bool $showDrafts
    ) {
        $this->contentManager = $contentManager;
        $this->entityManager = $entityManager;
        $this->contentMetadataInspector = $contentMetadataInspector;
        $this->metadataFactory = $metadataFactory;
        $this->contentRichEntityClass = $contentRichEntityClass;
        $this->showDrafts = $showDrafts;
    }

    /**
     * @param array<int|string> $ids
     * @param string $locale
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $contentRichEntities = $this->findEntitiesByIds($ids);

        return \array_values(
            \array_filter(
                \array_map(
                    function(ContentRichEntityInterface $contentRichEntity) use ($locale): ?Teaser {
                        $resolvedDimensionContent = $this->resolveContent($contentRichEntity, $locale);

                        if (!$resolvedDimensionContent) {
                            return null;
                        }

                        $data = $this->contentManager->normalize($resolvedDimensionContent);

                        return $this->createTeaser($resolvedDimensionContent, $data, $locale);
                    },
                    $contentRichEntities
                )
            )
        );
    }

    /**
     * @param B $dimensionContent
     * @param array<string, mixed> $data
     */
    protected function createTeaser(DimensionContentInterface $dimensionContent, array $data, string $locale): ?Teaser
    {
        $url = $this->getUrl($dimensionContent, $data);

        if (!$url) {
            return null;
        }

        /** @var string $title */
        $title = $this->getTitle($dimensionContent, $data);

        /** @var string $description */
        $description = $this->getDescription($dimensionContent, $data); // @phpstan-ignore-line

        /** @var string $moreText */
        $moreText = $this->getMoreText($dimensionContent, $data); // @phpstan-ignore-line

        /** @var int $mediaId */
        $mediaId = $this->getMediaId($dimensionContent, $data);

        return new Teaser(
            $dimensionContent->getResource()->getId(),
            $this->getResourceKey(),
            $locale,
            $title,
            $description,
            $moreText,
            $url,
            $mediaId,
            $this->getAttributes($dimensionContent, $data)
        );
    }

    /**
     * @param B $dimensionContent
     * @param array<string, mixed> $data
     */
    protected function getTitle(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            if ($excerptTitle = $dimensionContent->getExcerptTitle()) {
                return $excerptTitle;
            }
        }

        $title = $data['title'] ?? $data['name'] ?? null;

        return \is_string($title) ? $title : null;
    }

    /**
     * @param B $dimensionContent
     * @param array{description?: string|null} $data
     */
    protected function getDescription(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            if ($excerptDescription = $dimensionContent->getExcerptDescription()) {
                return $excerptDescription;
            }
        }

        return $data['description'] ?? null;
    }

    /**
     * @param B $dimensionContent
     * @param array{more?: string|null, moreText?: string|null} $data
     */
    protected function getMoreText(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            if ($excerptMore = $dimensionContent->getExcerptMore()) {
                return $excerptMore;
            }
        }

        return $data['more'] ?? $data['moreText'] ?? null;
    }

    /**
     * @param B $dimensionContent
     * @param mixed[] $data
     */
    protected function getMediaId(DimensionContentInterface $dimensionContent, array $data): ?int
    {
        if ($dimensionContent instanceof ExcerptInterface) {
            if ($excerptImage = $dimensionContent->getExcerptImage()) {
                // TODO FIXME create unit test for this
                return $excerptImage['id']; // @codeCoverageIgnore
            }
        }

        return null;
    }

    /**
     * @param B $dimensionContent
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    protected function getAttributes(DimensionContentInterface $dimensionContent, array $data): array
    {
        return [];
    }

    protected function getResourceKey(): string
    {
        $dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($this->contentRichEntityClass);

        return $dimensionContentClass::getResourceKey();
    }

    protected function getEntityIdField(): string
    {
        return 'id';
    }

    /**
     * @return class-string<T>
     */
    protected function getContentRichEntityClass(): string
    {
        return $this->contentRichEntityClass;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getStructureMetadataFactory(): StructureMetadataFactoryInterface
    {
        return $this->metadataFactory;
    }

    protected function getContentManager(): ContentManagerInterface
    {
        return $this->contentManager;
    }
}
