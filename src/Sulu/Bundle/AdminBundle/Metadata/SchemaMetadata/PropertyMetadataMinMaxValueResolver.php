<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Webmozart\Assert\Assert;

class PropertyMetadataMinMaxValueResolver
{
    /**
     * Result object has following structure:
     * <code>
     * ?int $min - value of min parameter
     * ?int $max - value of max parameter
     * </code>.
     *
     * @throws \InvalidArgumentException
     */
    public function resolveMinMaxValue(
        ContentPropertyMetadata $propertyMetadata,
        string $minParamName = 'min',
        string $maxParamName = 'max'
    ): object {
        $mandatory = $propertyMetadata->isRequired();

        $min = $propertyMetadata->getParameter($minParamName)['value'] ?? null;
        Assert::nullOrIntegerish($min, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or of type int',
            $minParamName,
            $propertyMetadata->getName()
        ));

        if (null !== $min) {
            $min = (int) $min;
        }

        Assert::nullOrGreaterThanEq($min, 0, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than or equal "0"',
            $minParamName,
            $propertyMetadata->getName()
        ));

        if ($mandatory) {
            if (null === $min) {
                $min = 1;
            }

            Assert::greaterThanEq($min, 1, \sprintf(
                'Because property "%s" is mandatory, parameter "%s" needs to be greater than or equal "1"',
                $propertyMetadata->getName(),
                $minParamName
            ));
        }

        $max = $propertyMetadata->getParameter($maxParamName)['value'] ?? null;
        Assert::nullOrIntegerish($max, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or of type int',
            $maxParamName,
            $propertyMetadata->getName()
        ));

        if (null !== $max) {
            $max = (int) $max;
        }

        Assert::nullOrGreaterThanEq($max, 1, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than or equal "1"',
            $maxParamName,
            $propertyMetadata->getName()
        ));

        if (null !== $min) {
            Assert::nullOrGreaterThanEq($max, $min, \sprintf(
                'Because parameter "%1$s" of property "%2$s" has value "%4$d", parameter "%3$s" needs to be greater than or equal "%4$d"',
                $minParamName,
                $propertyMetadata->getName(),
                $maxParamName,
                $min
            ));
        }

        return (object) [
            'min' => $min,
            'max' => $max,
        ];
    }
}
