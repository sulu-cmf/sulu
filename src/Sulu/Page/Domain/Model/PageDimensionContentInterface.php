<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Model;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

/**
 * @experimental
 */
interface PageDimensionContentInterface extends DimensionContentInterface, ExcerptInterface, SeoInterface, TemplateInterface, RoutableInterface, WorkflowInterface, AuthorInterface
{
    public function getTitle(): ?string;
}
