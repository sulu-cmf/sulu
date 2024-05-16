<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $articleRepository,
        private ContentWorkflowInterface $contentWorkflow
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionPageMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getUuid());

        $this->contentWorkflow->apply(
            $article,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );
    }
}
