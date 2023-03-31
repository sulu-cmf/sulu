<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class InvalidDefaultTypeException extends \Exception
{
    private string $name;

    private string $defaultType;

    /**
     * @var string[]
     */
    private array $availableTypes;

    /**
     * @param string[] $availableTypes
     */
    public function __construct(string $name, string $defaultType, array $availableTypes)
    {
        parent::__construct(\sprintf(
            'Property "%s" has invalid default-type "%s". Available types are %s',
            $name,
            $defaultType,
            \implode(
                ', ',
                \array_map(function($availableType) {
                    return '"' . $availableType . '"';
                }, $availableTypes)
            )
        ));
        $this->name = $name;
        $this->defaultType = $defaultType;
        $this->availableTypes = $availableTypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultType(): string
    {
        return $this->defaultType;
    }

    /**
     * @return string[]
     */
    public function getAvailableTypes(): array
    {
        return $this->availableTypes;
    }
}
