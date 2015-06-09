<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This interface is extended by the other handlers and indicates that
 * the implementing class implements one, some or all of:.
 *
 * - HandlerFlushInterface
 * - HandlerInvalidateStructureInterface
 * - HandlerUpdateResponseInterface
 */
interface HandlerInterface
{
}
