<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for Image selection, holds config for image selection and lazy loads images matches the ids.
 */
class MediaSelectionContainer implements ArrayableInterface
{
    /**
     * @var string[]
     */
    private $ids;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $displayOption;

    /**
     * @var string
     */
    #[Exclude]
    private $locale;

    /**
     * @var Media[]
     */
    #[Exclude]
    private $data;

    /**
     * @var string
     */
    #[Exclude]
    private $types;

    /**
     * @var MediaManagerInterface
     */
    #[Exclude]
    private $mediaManager;

    /**
     * @var ?int
     */
    private $permission;

    public function __construct(
        $config,
        $displayOption,
        $ids,
        $locale,
        $types,
        $mediaManager,
        $permission = null
    ) {
        $this->config = $config;
        $this->displayOption = $displayOption;
        $this->ids = $ids;
        $this->locale = $locale;
        $this->types = $types;
        $this->mediaManager = $mediaManager;
        $this->permission = $permission;
    }

    /**
     * returns data of container.
     *
     * @return Media[]
     */
    public function getData()
    {
        if (null === $this->data) {
            $this->data = $this->loadData($this->locale);
        }

        return $this->data;
    }

    /**
     * @param string $locale
     *
     * @return Media[]
     */
    private function loadData($locale)
    {
        if (!empty($this->ids)) {
            return $this->mediaManager->getByIds($this->ids, $locale, $this->permission);
        }

        return [];
    }

    /**
     * returns ids of container.
     *
     * @return string[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getDisplayOption()
    {
        return $this->displayOption;
    }

    /**
     * @return string
     */
    public function getTypes()
    {
        return $this->types;
    }

    public function __get($name)
    {
        return match ($name) {
            'data' => $this->getData(),
            'config' => $this->getConfig(),
            'ids' => $this->getIds(),
            'displayOption' => $this->getDisplayOption(),
            'types' => $this->getTypes(),
            default => null,
        };
    }

    public function __isset($name)
    {
        return 'data' == $name || 'config' == $name || 'ids' == $name || 'displayOption' == $name || 'types' == $name;
    }

    public function toArray($depth = null)
    {
        return [
            'config' => $this->getConfig(),
            'ids' => $this->getIds(),
            'types' => $this->getTypes(),
            'displayOption' => $this->getDisplayOption(),
        ];
    }
}
