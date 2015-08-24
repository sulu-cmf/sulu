<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Repository;

/**
 * Interface for combined repository account and contact.
 */
interface AccountContactRepositoryInterface
{
    public function findBy($filters, $page, $pageSize);
}
