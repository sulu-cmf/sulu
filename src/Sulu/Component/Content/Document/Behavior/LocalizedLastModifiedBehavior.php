<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Adds last modified date and toggler.
 */
interface LocalizedLastModifiedBehavior extends LocalizedBlameBehavior
{
    /**
     * Returns lastModifiedEnabled.
     *
     * @return bool
     */
    public function getLastModifiedEnabled();

    /**
     * Returns lastModified-date.
     *
     * @return \DateTime|null
     */
    public function getLastModified();

    /**
     * Set lastModified-date.
     *
     * @param \DateTime|null $lastModified
     *
     * @return void
     */
    public function setLastModified($lastModified);
}
