<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Container for a webspace definition
 * @package Sulu\Component\Webspace
 */
class Webspace
{
    /**
     * The name of the webspace
     * @var string
     */
    private $name;

    /**
     * The key of the webspace
     * @var string
     */
    private $key;

    /**
     * The localizations defined for this webspace
     * @var Localization[]
     */
    private $localizations;

    /**
     * The segments defined for this webspace
     * @var Segment[]
     */
    private $segments;

    /**
     * The theme of the webspace
     * @var Theme
     */
    private $theme;

    /**
     * The portals defined for this webspace
     * @var Portal[]
     */
    private $portals;

    /**
     * Sets the key of the webspace
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the webspace
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds a localization to the webspace
     * @param Localization $localization
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;
    }

    /**
     * Returns the localizations of this webspace
     * @param \Sulu\Component\Webspace\Localization[] $localizations
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the localizations of this webspace
     * @return \Sulu\Component\Webspace\Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Returns a list of all localizations and sublocalizations
     * @return \Sulu\Component\Webspace\Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = array();
        foreach ($this->getLocalizations() as $child) {
            $localizations[] = $child;
            $localizations = array_merge($localizations, $child->getAllLocalizations());
        }
        return $localizations;
    }

    /**
     * Returns the localization object for a given localization string
     * @param string $localization
     * @return Localization|null
     */
    public function getLocalization($localization)
    {
        $localizations = $this->getLocalizations();
        if (!empty($localizations)) {
            foreach ($localizations as $webspaceLocalization) {
                $result = $webspaceLocalization->findLocalization($localization);

                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Sets the name of the webspace
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the webspace
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a portal to the webspace
     * @param Portal $portal
     */
    public function addPortal(Portal $portal)
    {
        $this->portals[] = $portal;
    }

    /**
     * Sets the portals of this webspace
     * @param \Sulu\Component\Webspace\Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Returns the portals of this webspace
     * @return \Sulu\Component\Webspace\Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Adds a segment to the webspace
     * @param Segment $segment
     */
    public function addSegment(Segment $segment)
    {
        $this->segments[] = $segment;
    }

    /**
     * Sets the segments of this webspace
     * @param \Sulu\Component\Webspace\Segment[] $segments
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    /**
     * Returns the segments of this webspace
     * @return \Sulu\Component\Webspace\Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Sets the theme for this portal
     * @param \Sulu\Component\Webspace\Theme $theme
     */
    public function setTheme(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the theme for this portal
     * @return \Sulu\Component\Webspace\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }
}
