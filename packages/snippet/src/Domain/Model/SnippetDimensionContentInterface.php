<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Domain\Model;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @experimental
 *
 * @extends DimensionContentInterface<SnippetInterface>
 */
interface SnippetDimensionContentInterface extends DimensionContentInterface, TemplateInterface, WorkflowInterface
{
    public function getTitle(): ?string;
}
