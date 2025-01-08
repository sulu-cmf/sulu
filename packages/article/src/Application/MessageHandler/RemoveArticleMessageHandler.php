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

use Sulu\Article\Application\Message\RemoveArticleMessage;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveArticleMessageHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function __invoke(RemoveArticleMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());

        $this->articleRepository->remove($article);
    }
}
