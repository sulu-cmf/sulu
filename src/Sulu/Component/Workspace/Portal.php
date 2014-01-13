<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace;

/**
 * Container for a portal configuration
 * @package Sulu\Component\Portal
 */
class Portal
{
    /**
     * The name of the portal
     * @var string
     */
    private $name;

    /**
     * The key of the portal
     * @var string
     */
    private $key;

    /**
     * The url generation strategy for this portal
     * @var string
     */
    private $resourceLocatorStrategy;

    /**
     * An array of localizations
     * @var Localization[]
     */
    private $localizations;

    /**
     * @var Environment[]
     */
    private $environments;

    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * Sets the name of the portal
     * @param string $name The name of the portal
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the portal
     * @return string The name of the portal
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $resourceLocatorStrategy
     */
    public function setResourceLocatorStrategy($resourceLocatorStrategy)
    {
        $this->resourceLocatorStrategy = $resourceLocatorStrategy;
    }

    /**
     * @return string
     */
    public function getResourceLocatorStrategy()
    {
        return $this->resourceLocatorStrategy;
    }

    /**
     * Adds the given language to the portal
     * @param Localization $localization
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;
    }

    /**
     * Sets the localizations to this portal
     * @param \Sulu\Component\Workspace\Localization[] $localizations
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the languages of this portal
     * @return \Sulu\Component\Workspace\Localization[] The languages of this portal
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Adds an environment to this portal
     * @param $environment Environment The environment to add
     */
    public function addEnvironment($environment)
    {
        $this->environments[] = $environment;
    }

    /**
     * Sets the environments for this portal
     * @param \Sulu\Component\Workspace\Environment[] $environments
     */
    public function setEnvironments($environments)
    {
        $this->environments = $environments;
    }

    /**
     * Returns the environment for this portal
     * @return \Sulu\Component\Workspace\Environment[]
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * @param \Sulu\Component\Workspace\Workspace $workspace
     */
    public function setWorkspace(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    /**
     * @return \Sulu\Component\Workspace\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }
}
