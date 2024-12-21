<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

trait SetGetPrivatePropertyTrait
{
    /**
     * @return mixed
     */
    protected static function getPrivateProperty(object $object, string $propertyName)
    {
        $propertyReflection = static::getReflectionProperty($object, $propertyName);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    /**
     * @param mixed $value
     */
    protected static function setPrivateProperty(object $object, string $propertyName, $value): void
    {
        $propertyReflection = static::getReflectionProperty($object, $propertyName);
        $propertyReflection->setAccessible(true);

        $propertyReflection->setValue($object, $value);
    }

    /**
     * Get an object's property, including private properties declared in parent classes.
     *
     * @throws \Exception when the property doesn't exist in the object's class, or in its parent classes
     */
    protected static function getReflectionProperty(object $object, string $propertyName): \ReflectionProperty
    {
        $reflection = new \ReflectionClass($object);

        do {
            if ($reflection->hasProperty($propertyName)) {
                return $reflection->getProperty($propertyName);
            }

            // The property might be a private property declared in a parent class
            $reflection = $reflection->getParentClass();
        } while (false !== $reflection);

        throw new \LogicException(
            \sprintf(
                'Property %s does not exist in class %s, or in its parent classes',
                $propertyName,
                \get_class($object)
            )
        );
    }
}
