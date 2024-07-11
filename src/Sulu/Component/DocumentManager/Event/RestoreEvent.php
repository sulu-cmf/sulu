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

use PHPCR\NodeInterface;

class RestoreEvent extends AbstractMappingEvent
{
    /**
     * @param object $document
     * @param string $locale
     * @param string $version
     */
    public function __construct(
        $document,
        $locale,
        private $version,
        array $options = [],
    ) {
        $this->document = $document;
        $this->locale = $locale;
        $this->options = $options;
    }

    /**
     * Sets the node this event should operate on.
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * Returns the version, which should be restored.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
