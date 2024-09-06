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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\ArrayableInterface;

/**
 * This class represents the information for a given URL.
 */
class PortalInformation implements ArrayableInterface
{
    /**
     * The type of the match.
     *
     * @var int
     */
    private $type;

    /**
     * The webspace for this portal information.
     *
     * @var Webspace
     */
    private $webspace;

    /**
     * The portal for this portal information.
     *
     * @var Portal
     */
    private $portal;

    /**
     * The localization for this portal information.
     *
     * @var Localization
     */
    private $localization;

    /**
     * The segment for this portal information.
     *
     * @deprecated Is not set anymore since https://github.com/sulu/sulu/pull/5277
     *
     * @var Segment
     */
    private $segment;

    /**
     * The url for this portal information.
     *
     * @var string
     */
    private $url;

    /**
     * @var string The url to redirect to
     */
    private $redirect;

    /**
     * @var bool
     */
    private $main;

    /**
     * @var string
     */
    private $urlExpression;

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        $type,
        ?Webspace $webspace = null,
        ?Portal $portal = null,
        ?Localization $localization = null,
        $url = null,
        ?Segment $segment = null,
        $redirect = null,
        $main = false,
        $urlExpression = null,
        $priority = 0
    ) {
        $this->setType($type);
        $this->setWebspace($webspace);
        $this->setPortal($portal);
        $this->setLocalization($localization);
        $this->setUrl($url);
        $this->setRedirect($redirect);
        $this->setMain($main);
        $this->setUrlExpression($urlExpression);
        $this->setPriority($priority);

        if ($segment) {
            $this->setSegment($segment);
        }
    }

    /**
     * Sets the localization for this PortalInformation.
     *
     * @param Localization $localization
     */
    public function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * Returns the localization for this PortalInformation.
     *
     * @return Localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * Returns the localization for this PortalInformation.
     *
     * @return string
     */
    public function getLocale()
    {
        if (null === $this->localization) {
            return;
        }

        return $this->localization->getLocale();
    }

    /**
     * Sets the portal for this PortalInformation.
     *
     * @param Portal $portal
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * Returns the portal for this PortalInformation.
     *
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * Returns key of portal.
     */
    public function getPortalKey()
    {
        if (null === $this->portal) {
            return;
        }

        return $this->portal->getKey();
    }

    /**
     * Sets the redirect for the PortalInformation.
     *
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Returns the redirect for the PortalInformation.
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Sets the segment for the PortalInformation.
     *
     * @deprecated Is not set anymore since https://github.com/sulu/sulu/pull/5277
     *
     * @param Segment $segment
     */
    public function setSegment($segment)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.2',
            'Segment on the PortalInformation will be removed and should not be used anymore.'
        );
        $this->segment = $segment;
    }

    /**
     * Returns the segment for the PortalInformation.
     *
     * @deprecated Is not set anymore since https://github.com/sulu/sulu/pull/5277
     *
     * @return Segment
     */
    public function getSegment()
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.2',
            'Segment on the PortalInformation will be removed and should not be used anymore.'
        );

        return $this->segment;
    }

    /**
     * @deprecated Is not set anymore since https://github.com/sulu/sulu/pull/5277
     */
    public function getSegmentKey()
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.2',
            'Segment on the PortalInformation will be removed and should not be used anymore.'
        );

        return $this->segment ? $this->segment->getKey() : null;
    }

    /**
     * Sets the match type of this PortalInformation.
     *
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the match type of this PortalInformation.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the URL of this Portalinformation.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Returns the URL of this Portalinformation.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the host including the domain for the PortalInformation.
     *
     * @return string
     */
    public function getHost()
    {
        return \substr($this->url, 0, $this->getHostLength());
    }

    /**
     * Returns the prefix (the url without the host) for this PortalInformation.
     *
     * @return string
     */
    public function getPrefix()
    {
        $prefix = \substr($this->url, $this->getHostLength() + 1);

        return $prefix ? $prefix . '/' : null;
    }

    /**
     * Sets the webspace for this PortalInformation.
     *
     * @param Webspace $webspace
     */
    public function setWebspace($webspace)
    {
        $this->webspace = $webspace;
    }

    /**
     * Returns the webspace for this PortalInformation.
     *
     * @return Webspace|null
     */
    public function getWebspace()
    {
        return $this->webspace;
    }

    /**
     * Returns key of webspace.
     */
    public function getWebspaceKey()
    {
        if (null === $this->webspace) {
            return;
        }

        return $this->webspace->getKey();
    }

    /**
     * Returns true if url is main.
     *
     * @return bool
     */
    public function isMain()
    {
        return $this->main;
    }

    /**
     * Sets true if url is main.
     *
     * @param bool $main
     */
    public function setMain($main)
    {
        $this->main = $main;
    }

    /**
     * Returns expression for url.
     *
     * @return string
     */
    public function getUrlExpression()
    {
        return $this->urlExpression;
    }

    /**
     * Sets expression for url.
     *
     * @param string $urlExpression
     */
    public function setUrlExpression($urlExpression)
    {
        $this->urlExpression = $urlExpression;
    }

    /**
     * Calculate the length of the host part of the URL.
     *
     * @return int
     */
    private function getHostLength()
    {
        $hostLength = \strpos($this->url, '/');
        $hostLength = (false === $hostLength) ? \strlen($this->url) : $hostLength;

        return $hostLength;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function toArray($depth = null)
    {
        $result = [];
        $result['type'] = $this->getType();
        $result['webspace'] = $this->getWebspace()->getKey();
        $result['url'] = $this->getUrl();
        $result['main'] = $this->isMain();
        $result['priority'] = $this->getPriority();

        $portal = $this->getPortal();
        if ($portal) {
            $result['portal'] = $portal->getKey();
        }

        $localization = $this->getLocalization();
        if ($localization) {
            $result['localization'] = $localization->toArray();
        }

        $result['redirect'] = $this->getRedirect();

        $segment = $this->getSegment();
        if ($segment) {
            $result['segment'] = $segment->getKey();
        }

        $urlExpression = $this->getUrlExpression();
        if ($urlExpression) {
            $result['urlExpression'] = $urlExpression;
        }

        return $result;
    }
}
