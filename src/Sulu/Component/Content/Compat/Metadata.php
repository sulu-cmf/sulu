<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use JMS\Serializer\Annotation\Type;

/**
 * holds localized metadata.
 */
class Metadata
{
    /**
     * @var array
     */
    #[Type('array')]
    private $data;

    /**
     * @param array $metadata
     */
    public function __construct($metadata)
    {
        $this->data = $metadata;
    }

    /**
     * @param string $name meta data name
     * @param string $languageCode
     * @param string|null $default
     *
     * @return string|null
     */
    public function get($name, $languageCode, $default = null)
    {
        if (isset($this->data[$name]) && isset($this->data[$name][$languageCode])) {
            return $this->data[$name][$languageCode];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
