<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\MessageHandler;

use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionSnippetMessageHandler
{
    /**
     * @var SnippetRepositoryInterface
     */
    private $snippetRepository;

    /**
     * @var ContentWorkflowInterface
     */
    private $contentWorkflow;

    public function __construct(
        SnippetRepositoryInterface $snippetRepository,
        ContentWorkflowInterface $contentWorkflow
    ) {
        $this->snippetRepository = $snippetRepository;
        $this->contentWorkflow = $contentWorkflow;
    }

    public function __invoke(ApplyWorkflowTransitionSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->contentWorkflow->apply(
            $snippet,
            ['locale' => $message->getLocale()],
            $message->getTransitionName()
        );
    }
}
