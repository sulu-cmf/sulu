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

class Environment
{
    /**
     * The type of the environment (dev, staging, prod, ...).
     *
     * @var string
     */
    private $type;

    /**
     * The urls for this environment.
     *
     * @var Url[]
     */
    private $urls = [];

    /**
     * The custom-urls for this environment.
     *
     * @var CustomUrl[]
     */
    private $customUrls = [];

    /**
     * @var Url
     */
    private $mainUrl;

    public function __construct(?string $type = null)
    {
        if (null === $type) {
            @trigger_deprecation('sulu/sulu', '2.5', 'Not passing the type of the environment is deprecated.');
        } else {
            $this->type = $type;
        }
    }

    /**
     * Sets the type of the environment.
     *
     * @param string $type
     *
     * @deprecated
     */
    public function setType($type)
    {
        @trigger_deprecation('sulu/sulu', '2.5', 'Using the setter to set the type of the environment is deprecated. Use the constructor instead.');
        $this->type = $type;
    }

    /**
     * Returns the type of this environment.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Adds a new url to this environment.
     *
     * @param Url $url The url to add
     */
    public function addUrl(Url $url)
    {
        $this->urls[] = $url;

        $url->setEnvironment($this->getType());

        if ($url->isMain() || !$this->mainUrl) {
            $this->setMainUrl($url);
        }
    }

    /**
     * Sets the main url.
     */
    private function setMainUrl(Url $url)
    {
        if (null !== $this->mainUrl) {
            $this->mainUrl->setMain(false);
        }

        $this->mainUrl = $url;
        $this->mainUrl->setMain(true);
    }

    /**
     * Returns main url.
     *
     * @return Url
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * Set the urls for this environment.
     *
     * @param Url[] $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * Returns the urls for this environment.
     *
     * @return Url[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Returns custom-urls.
     *
     * @return CustomUrl[]
     */
    public function getCustomUrls()
    {
        return $this->customUrls;
    }

    /**
     * Sets custom-urls.
     *
     * @param CustomUrl[] $customUrls
     */
    public function setCustomUrls($customUrls)
    {
        $this->customUrls = $customUrls;
    }

    /**
     * Adds a new custom-url to this environment.
     */
    public function addCustomUrl(CustomUrl $customUrl)
    {
        $this->customUrls[] = $customUrl;
    }

    public function toArray($depth = null)
    {
        $res = [];
        $res['type'] = $this->getType();

        foreach ($this->getUrls() as $url) {
            $res['urls'][] = $url->toArray();
        }

        foreach ($this->getCustomUrls() as $customUrl) {
            $res['customUrls'][] = $customUrl->toArray();
        }

        return $res;
    }
}
