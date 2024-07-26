<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Util\ArrayableInterface;

/**
 * Contains information about custom-url.
 */
class CustomUrl implements ArrayableInterface
{
    /**
     * @param string $url
     */
    public function __construct(
        private $url = null
    ) {
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function toArray($depth = null)
    {
        return ['url' => $this->getUrl()];
    }
}
