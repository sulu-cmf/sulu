<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

/**
 * Defines the interface for the Factory of the DoctrineListBuilde.
 */
interface DoctrineListBuilderFactoryInterface
{
    public function create($entityName);
}
