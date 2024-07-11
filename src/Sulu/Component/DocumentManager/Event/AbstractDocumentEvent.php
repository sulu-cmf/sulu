<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

abstract class AbstractDocumentEvent extends AbstractEvent
{
    /**
     * @param object $document
     */
    public function __construct(private $document)
    {
    }

    /**
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    public function getDebugMessage()
    {
        return \sprintf(
            'd:%s',
            $this->document ? \spl_object_hash($this->document) : '<no document>'
        );
    }
}
