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

use Sulu\Article\Application\Message\CopyLocaleArticleMessage;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyLocaleArticleMessageHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    /**
     * @var ContentCopierInterface
     */
    private $contentCopier;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        ContentCopierInterface $contentCopier
    ) {
        $this->articleRepository = $articleRepository;
        $this->contentCopier = $contentCopier;
    }

    public function __invoke(CopyLocaleArticleMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());

        $this->contentCopier->copy(
            $article,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getSourceLocale(),
            ],
            $article,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getTargetLocale(),
            ]
        );
    }
}
