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

use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemovePageMessageHandler
{
    public function __construct(private PageRepositoryInterface $pageRepository)
    {
    }

    public function __invoke(RemovePageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getUuid());

        $this->pageRepository->remove($page);
    }
}
