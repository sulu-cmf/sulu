<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior;

use Sulu\Component\DocumentManager\Version;

/**
 * This behavior has to be attached to documents which should be versionable.
 */
interface VersionBehavior
{
    /**
     * Returns all the versions of this document.
     *
     * @return Version[]
     */
    public function getVersions();

    /**
     * Sets the versions for this document.
     *
     * @param Version[] $versions
     *
     * @return void
     */
    public function setVersions($versions);
}
