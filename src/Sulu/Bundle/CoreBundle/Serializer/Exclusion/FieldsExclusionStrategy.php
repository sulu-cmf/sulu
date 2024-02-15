<?php

declare(strict_types=1);

namespace Sulu\Bundle\CoreBundle\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class FieldsExclusionStrategy implements ExclusionStrategyInterface
{
    /** @param array<int, string> $requestedFields */
    public function __construct(private array $requestedFields)
    {
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if ($this->requestedFields === []) {
            return false;
        }

        return !in_array($property->serializedName, $this->requestedFields, true);
    }
}
