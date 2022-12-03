<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Domain\Model;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
// use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

/**
 * @experimental
 *
 * @extends DimensionContentInterface<ArticleInterface>
 */
interface ArticleDimensionContentInterface extends DimensionContentInterface, ExcerptInterface, SeoInterface, TemplateInterface, RoutableInterface, WorkflowInterface, /*WebspaceInterface,*/ AuthorInterface
{
    public function getTitle(): ?string;
}
