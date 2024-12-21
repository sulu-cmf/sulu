<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Tests\Application\Entity;

use JMS\Serializer\Annotation\Groups;

class TestResource
{
    /**
     * @var string
     */
    #[Groups(['restoreSerializationGroup'])]
    private $property1 = 'value-1';

    /**
     * @var string
     */
    #[Groups(['otherSerializationGroup'])]
    private $property2 = 'value-2';
}
