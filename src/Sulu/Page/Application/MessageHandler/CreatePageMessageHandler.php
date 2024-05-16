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

use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
final class CreatePageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        /** @var PageMapperInterface[] */
        private iterable $pageMappers
    ) {
    }

    /**
     * @throws PageNotFoundException
     */
    public function __invoke(CreatePageMessage $message): PageInterface
    {
        $data = $message->getData();
        $page = $this->pageRepository->createNew($message->getUuid());

        $page->setWebspaceKey($message->getWebspaceKey());

        if ('' !== $message->getParentId()) {
            $page->setParent($this->pageRepository->getOneBy($message->getParentId()));
        }

        foreach ($this->pageMappers as $pageMapper) {
            $pageMapper->mapPageData($page, $data);
        }

        $this->pageRepository->add($page);

        return $page;
    }
}
