<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;

/**
 * All content documents can have localized content, but only
 * content documents implementing this behavior can change the 
 * structure type for each localization.
 */
interface LocalizedContentBehavior
{
}
