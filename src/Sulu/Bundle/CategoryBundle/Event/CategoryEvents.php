<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Event;

final class CategoryEvents
{
    /**
     * The category.delete event is thrown after a category got deleted.
     * The event listener receives a CategoryDeleteEvent instance.
     *
     * @var string
     */
    public const CATEGORY_DELETE = 'sulu.category.delete';
}
