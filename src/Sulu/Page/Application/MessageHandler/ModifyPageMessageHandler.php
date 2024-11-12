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
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
final class ModifyPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        /** @var PageMapperInterface[] */
        private iterable $pageMappers
    ) {
    }

    public function __invoke(ModifyPageMessage $message): PageInterface
    {
        $data = $message->getData();
        $page = $this->pageRepository->getOneBy($message->getUuid());

        foreach ($this->pageMappers as $pageMapper) {
            $pageMapper->mapPageData($page, $data);
        }

        return $page;
    }
}
