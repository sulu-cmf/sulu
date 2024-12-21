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

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type;

/**
 * Represents a parameter of a property.
 */
class PropertyParameter implements \JsonSerializable
{
    /**
     * @var string
     */
    #[Type('string')]
    #[Groups(['frontend'])]
    private $name;

    /**
     * @var string|bool|array
     */
    #[Groups(['frontend'])]
    private $value;

    /**
     * @var string
     */
    #[Type('string')]
    private $type;

    /**
     * @var Metadata
     */
    #[Type('Sulu\Component\Content\Compat\Metadata')]
    private $metadata;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string|null $type
     * @param string|bool|array $value
     * @param array $metadata
     */
    public function __construct($name, $value, $type = null, $metadata = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->metadata = new Metadata($metadata);
    }

    /**
     * Returns name of property param.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns value of property param.
     *
     * @return array|bool|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns type of property param.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns title of property param.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->metadata->get('title', $languageCode, \ucfirst($this->name));
    }

    /**
     * Returns TRUE if parameter has a localized title the given language.
     *
     * @param string $languageCode
     *
     * @return bool
     */
    public function hasTitle($languageCode)
    {
        return null !== $this->metadata->get('title', $languageCode);
    }

    /**
     * Returns infoText of property param.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getInfoText($languageCode)
    {
        return $this->metadata->get('info_text', $languageCode, '');
    }

    /**
     * Returns placeholder of property param.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getPlaceholder($languageCode)
    {
        return $this->metadata->get('placeholder', $languageCode, '');
    }

    public function __toString()
    {
        $value = $this->getValue();

        if (\is_string($value)) {
            return $value;
        } elseif (\is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return '';
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'value' => $this->getValue(),
            'type' => $this->getType(),
        ];
    }
}
