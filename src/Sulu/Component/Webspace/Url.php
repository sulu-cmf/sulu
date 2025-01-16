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

class Url implements ArrayableInterface
{
    /**
     * The language to which the url leads.
     *
     * @var string
     */
    private $language;

    /**
     * The country to which the url leads.
     *
     * @var string
     */
    private $country;

    /**
     * The segment to which the url leads.
     *
     * @var string
     */
    private $segment;

    /**
     * The url to which this url redirects.
     *
     * @var string
     */
    private $redirect;

    /**
     * Indicate the main url.
     *
     * @var bool
     */
    private $main;

    /**
     * @param string $url
     * @param string $environment
     */
    public function __construct(
        private $url = null,
        private $environment = null
    ) {
        if (null !== $environment) {
            @trigger_deprecation('sulu/sulu', '2.5', 'Passing an environment to the URL is deprecated.');
        }
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

    /**
     * Returns the url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the country to which this url leads.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country to which this url leads.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the language to which this url leads.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns the language to which this url leads.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets the segment to which this url leads.
     *
     * @param string $segment
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Returns the segment to which this url leads.
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * Sets the redirect for this url.
     *
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Returns the redirect url.
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Return main flag.
     *
     * @return bool
     */
    public function isMain()
    {
        return $this->main;
    }

    /**
     * Sets main flag.
     *
     * @param bool $main
     */
    public function setMain($main)
    {
        $this->main = $main;
    }

    /**
     * Returns the environment.
     *
     * @return string
     *
     * @deprecated use Portal::getEnvironment() instead
     */
    public function getEnvironment()
    {
        @trigger_deprecation('sulu/sulu', '2.5', 'getting and setting the environment on the url is deprecated use the portals environment.');

        return $this->environment;
    }

    /**
     * Sets the environment.
     *
     * @param string $environment
     *
     * @deprecated
     */
    public function setEnvironment($environment)
    {
        @trigger_deprecation('sulu/sulu', '2.5', 'getting and setting the environment on the url is deprecated use the portals environment.');
        $this->environment = $environment;
    }

    /**
     * Checks if this URL handles the locale for the given language and country.
     *
     * @param string $language
     * @param string $country
     *
     * @return bool
     */
    public function isValidLocale($language, $country)
    {
        return ($this->getLanguage() === $language && $this->getCountry() === $country)
            || (empty($this->getLanguage()) && empty($this->getCountry()));
    }

    public function toArray($depth = null)
    {
        $res = [];
        $res['url'] = $this->getUrl();
        $res['language'] = $this->getLanguage();
        $res['country'] = $this->getCountry();
        $res['segment'] = $this->getSegment();
        $res['redirect'] = $this->getRedirect();
        $res['main'] = $this->isMain();
        $res['environment'] = $this->getEnvironment();

        return $res;
    }
}
