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

use Sulu\Snippet\Application\Message\RemoveSnippetMessage;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveSnippetMessageHandler
{
    /**
     * @var SnippetRepositoryInterface
     */
    private $snippetRepository;

    public function __construct(SnippetRepositoryInterface $snippetRepository)
    {
        $this->snippetRepository = $snippetRepository;
    }

    public function __invoke(RemoveSnippetMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());

        $this->snippetRepository->remove($snippet);
    }
}
