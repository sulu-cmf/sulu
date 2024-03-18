<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Represents a localization of a webspace definition.
 */
class Localization implements \JsonSerializable, ArrayableInterface
{
    public const UNDERSCORE = 'de_at';

    public const DASH = 'de-at';

    public const ISO6391 = 'de-AT';

    public const LCID = 'de_AT';

    /**
     * Create an instance of localization for given locale.
     *
     * @param string $locale
     * @param string $format
     *
     * @return Localization
     */
    public static function createFromString($locale, $format = self::UNDERSCORE)
    {
        $delimiter = '-';
        if (\in_array($format, [self::UNDERSCORE, self::LCID])) {
            $delimiter = '_';
        }

        $parts = \explode($delimiter, $locale);

        $localization = new self(\strtolower($parts[0]));
        if (\count($parts) > 1) {
            $localization->setCountry(\strtolower($parts[1]));
        }

        return $localization;
    }

    /**
     * The language of the localization.
     *
     * @var string|null
     *
     * @Groups({"frontend", "Default"})
     */
    private $language;

    /**
     * The country of the localization.
     *
     * @var string|null
     *
     * @Groups({"frontend", "Default"})
     */
    private $country;

    /**
     * Defines how the generation of shadow pages should be handled.
     *
     * @var string|null
     *
     * @Groups({"frontend", "Default"})
     */
    private $shadow;

    /**
     * The sub localizations of this one.
     *
     * @var Localization[]
     *
     * @Groups({"frontend", "Default"})
     */
    private $children = [];

    /**
     * The parent localization.
     *
     * @var Localization
     *
     * @Groups({"frontend", "Default"})
     */
    private $parent;

    /**
     * Defines whether this localization is the default one or not.
     *
     * @var bool
     *
     * @Groups({"frontend", "Default"})
     */
    private $default;

    public function __construct($language = null, $country = null)
    {
        $this->language = $language;
        $this->country = $country;
    }

    /**
     * Sets the country of this localization.
     *
     * @param string|null $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country of this localization.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the language of this localization.
     *
     * @param string|null $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns the language of this localization.
     *
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets how to handle shadow pages for this localization.
     *
     * @param string|null $shadow
     */
    public function setShadow($shadow)
    {
        $this->shadow = $shadow;
    }

    /**
     * Returns how to handle shadow pages for this localization.
     *
     * @return string|null
     */
    public function getShadow()
    {
        return $this->shadow;
    }

    /**
     * Adds a new child localization.
     */
    public function addChild(self $child)
    {
        $this->children[] = $child;
    }

    /**
     * Sets the children of the localization.
     *
     * @param Localization[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /**
     * Returns the children of the localization.
     *
     * @return Localization[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Returns the localization code, which is a combination of the language and the country.
     *
     * @param string $delimiter between language and country
     *
     * @return string|null
     *
     * @VirtualProperty
     * @Groups({"frontend", "Default"})
     *
     * @deprecated use getLocale instead
     */
    public function getLocalization($delimiter = '_')
    {
        @trigger_deprecation('sulu/sulu', '1.2', __METHOD__ . '() is deprecated and will be removed in 2.0. Use getLocale() instead.');

        $localization = $this->getLanguage() ?? '';
        if (null != $this->getCountry()) {
            $localization .= $delimiter . $this->getCountry();
        }

        return $localization;
    }

    /**
     * Returns the localization code, which is a combination of the language and the country in a specific format.
     *
     * @param string $format requested localization format
     *
     * @return string
     *
     * @VirtualProperty
     * @Groups({"frontend", "Default"})
     */
    public function getLocale($format = self::UNDERSCORE)
    {
        $localization = \strtolower($this->getLanguage() ?? '');

        $country = $this->getCountry() ?? '';
        if ('' !== $country) {
            $delimiter = '-';

            switch ($format) {
                case self::UNDERSCORE:
                    $delimiter = '_';
                    break;
                case self::ISO6391:
                    $country = \strtoupper($country);
                    break;
                case self::LCID:
                    $delimiter = '_';
                    $country = \strtoupper($country);
                    break;
            }

            $localization .= $delimiter . $country;
        }

        return $localization;
    }

    /**
     * Sets the parent of this localization.
     */
    public function setParent(self $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent of this localization.
     *
     * @return Localization
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets if this localization is the default one.
     *
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Returns if this localization is the default one.
     *
     * @return bool True if this is the default localization, otherwise false
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param string $localization
     *
     * @return Localization|null
     */
    public function findLocalization($localization)
    {
        if ($this->getLocale() == $localization) {
            return $this;
        }

        $children = $this->getChildren();
        if (!empty($children)) {
            foreach ($children as $childLocalization) {
                $result = $childLocalization->findLocalization($localization);
                if ($result) {
                    return $result;
                }
            }
        }

        return;
    }

    /**
     * Returns a list of all localizations and sublocalizations.
     *
     * @return Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = [];
        foreach ($this->getChildren() as $child) {
            $localizations[] = $child;
            $localizations = \array_merge($localizations, $child->getAllLocalizations());
        }

        return $localizations;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getLocale();
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'localization' => $this->getLocale(),
            'name' => $this->getLocale(),
        ];
    }

    public function toArray($depth = null)
    {
        $res = [];
        $res['country'] = $this->getCountry();
        $res['language'] = $this->getLanguage();
        $res['localization'] = $this->getLocale();
        $res['default'] = $this->isDefault();
        $res['children'] = [];

        $children = $this->getChildren();
        if ($children) {
            foreach ($this->getChildren() as $childLocalization) {
                $res['children'][] = $childLocalization->toArray(null);
            }
        }

        $res['shadow'] = $this->getShadow();

        return $res;
    }
}
