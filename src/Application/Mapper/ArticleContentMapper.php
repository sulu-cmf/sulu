<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\Mapper;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Webmozart\Assert\Assert;

/**
 * @experimental
 *
 * @internal This class should be instantiated inside a project.
 *           Use the message to create or modify an article.
 *           Or the inject all the mappers into a custom service.
 *           Create an own Mapper to extend the mapper with
 *           custom logic.
 */
final class ArticleContentMapper implements ArticleMapperInterface
{
    /**
     * @var ContentPersisterInterface
     */
    private $contentPersister;

    public function __construct(ContentPersisterInterface $contentPersister)
    {
        $this->contentPersister = $contentPersister;
    }

    public function mapArticleData(ArticleInterface $article, array $data): void
    {
        $locale = $data['locale'] ?? null;
        Assert::string($locale);

        $dimensionAttributes = ['locale' => $locale];

        // TODO this will be changed to `$article`, `$dimensionAttributes`, `$data`
        $this->contentPersister->persist($article, $data, $dimensionAttributes);
    }
}
