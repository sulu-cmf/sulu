<?php

declare(strict_types=1);

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleArticleSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    private ArticleRepositoryInterface $articleRepository;

    private ContentManagerInterface $contentManager;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        ContentManagerInterface    $contentManager,
        ReferenceStoreInterface    $referenceStore,
    )
    {
        parent::__construct('Article');

        $this->articleRepository = $articleRepository;
        $this->contentManager = $contentManager;
        $this->referenceStore = $referenceStore;
    }

    public function getContentData(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (null === $uuid) {
            return null;
        }

        $dimensionAttributes = [
            'locale' => $property->getStructure()->getLanguageCode(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ];

        $article = $this->articleRepository->getOneBy(
            filters: \array_merge(
                [
                    'uuid' => $uuid,
                ],
                $dimensionAttributes,
            ),
            selects: [
                ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true,
            ]);


        $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);
        return $this->contentManager->normalize($dimensionContent);
    }

    public function preResolve(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (null === $uuid) {
            return;
        }

        $this->referenceStore->add($uuid);
    }
}

