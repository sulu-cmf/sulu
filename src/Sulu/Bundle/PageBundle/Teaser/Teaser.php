<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Teaser;

/**
 * Contains teaser information.
 */
class Teaser
{
    /**
     * @param int|string $id
     * @param string $type
     * @param string $locale
     * @param string $title
     * @param string $description
     * @param string $moreText
     * @param string $url
     * @param int $mediaId
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private $id,
        private $type,
        private $locale,
        private $title,
        private $description,
        private $moreText,
        private $url,
        private $mediaId,
        private $attributes = [],
    ) {
    }

    /**
     * Returns id.
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns more-text.
     *
     * @return string
     */
    public function getMoreText()
    {
        return $this->moreText;
    }

    /**
     * Returns media-id.
     *
     * @return int
     */
    public function getMediaId()
    {
        return $this->mediaId;
    }

    /**
     * Returns url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Merges given data with this teaser.
     *
     * @return Teaser
     */
    public function merge(array $item)
    {
        $this->title = $this->getValue('title', $item, $this->getTitle());
        $this->description = $this->getValue('description', $item, $this->getDescription());
        $this->moreText = $this->getValue('moreText', $item, $this->getMoreText());
        $this->url = $this->getValue('url', $item, $this->getUrl());
        $this->mediaId = $this->getValue('mediaId', $item, $this->getMediaId());

        return $this;
    }

    /**
     * Returns array-value by name or default value.
     *
     * @param string $name
     */
    private function getValue($name, array $item, $default)
    {
        if (!\array_key_exists($name, $item)) {
            return $default;
        }

        return $item[$name];
    }
}
