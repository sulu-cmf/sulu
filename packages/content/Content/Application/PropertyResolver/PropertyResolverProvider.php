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

namespace Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver;

use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

class PropertyResolverProvider
{
    /**
     * @var PropertyResolverInterface[]
     */
    private array $propertyResolvers;

    /**
     * @param iterable<PropertyResolverInterface> $propertyResolvers
     */
    public function __construct(iterable $propertyResolvers)
    {
        $this->propertyResolvers = \iterator_to_array($propertyResolvers);
    }

    public function getPropertyResolver(string $type): PropertyResolverInterface
    {
        if (!\array_key_exists($type, $this->propertyResolvers)) {
            return $this->propertyResolvers['default'];
        }

        return $this->propertyResolvers[$type];
    }
}
