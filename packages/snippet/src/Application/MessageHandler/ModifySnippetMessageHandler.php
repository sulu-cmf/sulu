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

use Sulu\Snippet\Application\Mapper\SnippetMapperInterface;
use Sulu\Snippet\Application\Message\ModifySnippetMessage;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a SnippetMapper to extend this Handler.
 */
final class ModifySnippetMessageHandler
{
    /**
     * @var SnippetRepositoryInterface
     */
    private $snippetRepository;

    /**
     * @var iterable<SnippetMapperInterface>
     */
    private $snippetMappers;

    /**
     * @param iterable<SnippetMapperInterface> $snippetMappers
     */
    public function __construct(
        SnippetRepositoryInterface $snippetRepository,
        iterable $snippetMappers
    ) {
        $this->snippetRepository = $snippetRepository;
        $this->snippetMappers = $snippetMappers;
    }

    public function __invoke(ModifySnippetMessage $message): SnippetInterface
    {
        $identifier = $message->getIdentifier();
        $data = $message->getData();
        $snippet = $this->snippetRepository->getOneBy($identifier);

        foreach ($this->snippetMappers as $snippetMapper) {
            $snippetMapper->mapSnippetData($snippet, $data);
        }

        return $snippet;
    }
}
