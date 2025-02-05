<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

class LinkConfigurationBuilder
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $listAdapter;

    /**
     * @var string[]
     */
    private $displayProperties;

    /**
     * @var string
     */
    private $overlayTitle;

    /**
     * @var string
     */
    private $emptyText;

    /**
     * @var string
     */
    private $icon;

    public static function create(): self
    {
        return new self();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setResourceKey(string $resourceKey): self
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function setListAdapter(string $listAdapter): self
    {
        $this->listAdapter = $listAdapter;

        return $this;
    }

    /**
     * @param string[] $displayProperties
     */
    public function setDisplayProperties(array $displayProperties): self
    {
        $this->displayProperties = $displayProperties;

        return $this;
    }

    public function setOverlayTitle(string $overlayTitle): self
    {
        $this->overlayTitle = $overlayTitle;

        return $this;
    }

    public function setEmptyText(string $emptyText): self
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLinkConfiguration(): LinkConfiguration
    {
        return new LinkConfiguration(
            $this->title,
            $this->resourceKey,
            $this->listAdapter,
            $this->displayProperties,
            $this->overlayTitle,
            $this->emptyText,
            $this->icon
        );
    }
}
