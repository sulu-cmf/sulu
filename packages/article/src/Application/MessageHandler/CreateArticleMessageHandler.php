<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\MessageHandler;

use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a ArticleMapper to extend this Handler.
 */
final class CreateArticleMessageHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    /**
     * @var iterable<ArticleMapperInterface>
     */
    private $articleMappers;

    /**
     * @param iterable<ArticleMapperInterface> $articleMappers
     */
    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        iterable $articleMappers
    ) {
        $this->articleRepository = $articleRepository;
        $this->articleMappers = $articleMappers;
    }

    public function __invoke(CreateArticleMessage $message): ArticleInterface
    {
        $data = $message->getData();
        $article = $this->articleRepository->createNew($message->getUuid());

        foreach ($this->articleMappers as $articleMapper) {
            $articleMapper->mapArticleData($article, $data);
        }

        $this->articleRepository->add($article);

        return $article;
    }
}
