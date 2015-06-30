<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Event;

use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is emitted along with the listbuilder.create event
 *
 * Class ListBuilderCreateEvent
 * @package Sulu\Component\Rest\ListBuilder\Event
 */
class ListBuilderCreateEvent extends Event
{
    /**
     * @var ListbuilderInterface
     */
    protected $listBuilder;

    public function __construct(ListbuilderInterface $lb)
    {
        $this->listBuilder = $lb;
    }

    /**
     * @return ListBuilderInterface
     */
    public function getListBuilder()
    {
        return $this->listBuilder;
    }
}
