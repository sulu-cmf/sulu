<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Traits;

trait SetGetPrivatePropertyTrait
{
    /**
     * @return mixed
     */
    protected static function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($propertyName);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    /**
     * @param mixed $value
     */
    protected static function setPrivateProperty(object $object, string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($propertyName);
        $propertyReflection->setAccessible(true);

        $propertyReflection->setValue($object, $value);
    }
}
