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

use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;

/**
 * Documents implementing this behavior can have structures applied to them.
 *
 * Content is set by binding raw data to the StructureInterface retried
 * through the getContent method.
 *
 * Content is accessed as folows:
 *
 * ````
 * $this->getContent()->getProperty('foo')->getValue();
 * ````
 */
interface StructureBehavior extends LocaleBehavior
{
    /**
     * Return the type of the structure used for the content.
     *
     * @return string|null
     */
    public function getStructureType();

    /**
     * Set the structure type used for the content.
     *
     * @param string|null $structureType
     */
    public function setStructureType($structureType);

    /**
     * Return the StructureInterface instance.
     *
     * @return StructureInterface
     */
    public function getStructure();
}
