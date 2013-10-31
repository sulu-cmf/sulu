<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

class Environment
{
    /**
     * The type of the environment (dev, staging, prod, ...)
     * @var string
     */
    private $type;

    /**
     * The urls for this environment
     * @var Url[]
     */
    private $urls;

    /**
     * Sets the tye of this environment
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the type of this environment
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Adds a new url to this environment
     * @param $url Url The url to add
     */
    public function addUrl(Url $url)
    {
        $this->urls[] = $url;
    }

    /**
     * Set the urls for this environment
     * @param \Sulu\Component\Portal\Url[] $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * Returns the urls for this environment
     * @return \Sulu\Component\Portal\Url[]
     */
    public function getUrls()
    {
        return $this->urls;
    }
}
