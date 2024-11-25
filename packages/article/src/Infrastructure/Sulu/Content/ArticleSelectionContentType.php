<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ArticleSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    private ReferenceStoreInterface $referenceStore;
    private ContentManagerInterface $contentManager;
    private ArticleRepositoryInterface $articleRepository;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        ContentManagerInterface $contentManager,
        ReferenceStoreInterface $referenceStore
    ) {
        parent::__construct('Article', []);
        $this->referenceStore = $referenceStore;
        $this->contentManager = $contentManager;
        $this->articleRepository = $articleRepository;
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if (null === $value || !\is_array($value) || 0 === \count($value)) {
            return [];
        }

        $dimensionAttributes = [
            'locale' => $property->getStructure()->getLanguageCode(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ];

        $article = $this->articleRepository->findBy(
            filters: \array_merge(
                ['uuids' => $value],
                $dimensionAttributes,
            ),
            selects: [
                ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true,
            ]);

        $result = [];
        foreach ($article as $article) {
            $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);
            $result[\array_search($article->getUuid(), $value, false)] = $this->contentManager->normalize($dimensionContent);
        }

        \ksort($result);

        return \array_values($result);
    }

    public function preResolve(PropertyInterface $property): void
    {
        $uuids = $property->getValue();
        if (!\is_array($uuids)) {
            return;
        }

        foreach ($uuids as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }
}
