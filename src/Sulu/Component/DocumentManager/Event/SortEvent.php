<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use PHPCR\NodeInterface;

class SortEvent extends AbstractMappingEvent
{
    public function __construct(object $document, string $locale)
    {
        $this->document = $document;
        $this->locale = $locale;
    }

    public function getDebugMessage(): string
    {
        return \sprintf('%s sorting', parent::getDebugMessage());
    }

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }
}
