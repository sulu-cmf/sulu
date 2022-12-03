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
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a ArticleMapper to extend this Handler.
 */
final class ModifyArticleMessageHandler
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

    public function __invoke(ModifyArticleMessage $message): ArticleInterface
    {
        $identifier = $message->getIdentifier();
        $data = $message->getData();
        $article = $this->articleRepository->getOneBy($identifier);

        foreach ($this->articleMappers as $articleMapper) {
            $articleMapper->mapArticleData($article, $data);
        }

        return $article;
    }
}
