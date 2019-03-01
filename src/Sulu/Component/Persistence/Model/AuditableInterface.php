<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

/**
 * Composite interface of TimestampableInterface and UserBlameInterface.
 */
interface AuditableInterface extends TimestampableInterface, UserBlameInterface
{
}
