<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

/**
 * This exception is thrown if a reset-password email is requested, but a token was already generated shortly before
 * @package Sulu\Bundle\SecurityBundle\Security\Exception
 */
class TokenAlreadyRequestedException extends SecurityException
{
    /**
     * The time interval in which only one token can be requested
     *
     * @var \DateInterval
     */
    private $interval;

    public function __construct($interval)
    {
        parent::__construct('a token has already been generated', 1003);
        $this->interval = $interval;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'interval' => (new \DateTime('@0'))->add($this->interval)->getTimestamp() // interval in seconds
        );
    }
}
