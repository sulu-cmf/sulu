<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use Sulu\Component\DocumentManager\Behavior\LocalizedLastModifiedBehavior as SuluLocalizedLastModifiedBehavior;

if (\interface_exists(SuluLocalizedLastModifiedBehavior::class)) {
    /**
     * @internal BC Layer for Sulu <2.6
     */
    interface LocalizedLastModifiedBehavior extends SuluLocalizedLastModifiedBehavior
    {
    }
} else {
    /**
     * @internal BC Layer for Sulu <2.6
     */
    interface LocalizedLastModifiedBehavior
    {
    }
}
