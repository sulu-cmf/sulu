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

use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Application\Message\CopyLocaleSnippetMessage;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyLocaleSnippetMessageHandler
{
    /**
     * @var SnippetRepositoryInterface
     */
    private $snippetRepository;

    /**
     * @var ContentCopierInterface
     */
    private $contentCopier;

    public function __construct(
        SnippetRepositoryInterface $snippetRepository,
        ContentCopierInterface $contentCopier
    ) {
        $this->snippetRepository = $snippetRepository;
        $this->contentCopier = $contentCopier;
    }

    public function __invoke(CopyLocaleSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->contentCopier->copy(
            $snippet,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getSourceLocale(),
            ],
            $snippet,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getTargetLocale(),
            ]
        );
    }
}
