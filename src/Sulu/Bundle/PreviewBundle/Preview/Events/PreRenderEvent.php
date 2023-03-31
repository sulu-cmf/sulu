<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Events;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is thrown right before a preview will be rendered.
 */
class PreRenderEvent extends Event
{
    private \Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes $requestAttributes;

    public function __construct(RequestAttributes $requestAttributes)
    {
        $this->requestAttributes = $requestAttributes;
    }

    /**
     * Returns requestAttributes.
     *
     * @return RequestAttributes
     */
    public function getRequestAttributes()
    {
        return $this->requestAttributes;
    }

    /**
     * Returns request attribute with given name.
     *
     * @param string $name
     * @param mixed|null $default
     */
    public function getAttribute($name, $default = null)
    {
        return $this->requestAttributes->getAttribute($name, $default);
    }
}
