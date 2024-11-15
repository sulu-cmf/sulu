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

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionArticleMessageHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    /**
     * @var ContentWorkflowInterface
     */
    private $contentWorkflow;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        ContentWorkflowInterface $contentWorkflow
    ) {
        $this->articleRepository = $articleRepository;
        $this->contentWorkflow = $contentWorkflow;
    }

    public function __invoke(ApplyWorkflowTransitionArticleMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());

        $this->contentWorkflow->apply(
            $article,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );
    }
}
