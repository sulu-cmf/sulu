<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

/**
 * This exception is thrown if a reset-password email is requested, but a token was already generated shortly before.
 *
 * @deprecated since Sulu 2.1.1 and will be removed in Sulu 3.0
 */
class TokenAlreadyRequestedException extends SecurityException
{
    /**
     * @param \DateInterval $interval the time interval in which only one token can be requested
     */
    public function __construct(private \DateInterval $interval)
    {
        parent::__construct('a token has already been generated', 1003);
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'interval' => (new \DateTime('@0'))->add($this->interval)->getTimestamp(), // interval in seconds
        ];
    }
}
