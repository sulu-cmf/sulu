<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Mapping;

/**
 * The document has a parent document.
 */
interface ParentBehavior
{
    /**
     * Return the parent document for this document.
     *
     * @return object
     */
    public function getParent();

    /**
     * Set the parent document for this document.
     *
     * @param object $document
     *
     * @return void
     */
    public function setParent($document);
}
