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

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache.
 */
class StructureManager implements StructureManagerInterface
{
    public function __construct(
        private StructureMetadataFactoryInterface $structureFactory,
        private DocumentInspector $inspector,
        private LegacyPropertyFactory $propertyFactory,
        private array $typeMap,
    ) {
    }

    public function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        try {
            $metadata = $this->structureFactory->getStructureMetadata($type, $key);
        } catch (StructureTypeNotFoundException $exception) {
            return;
        }

        return $this->wrapStructure($type, $metadata);
    }

    public function getStructures($type = Structure::TYPE_PAGE)
    {
        $wrappedStructures = [];
        $structures = $this->structureFactory->getStructures($type);

        foreach ($structures as $structure) {
            $wrappedStructures[] = $this->wrapStructure($type, $structure);
        }

        return $wrappedStructures;
    }

    public function wrapStructure($type, StructureMetadata $structure)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Invalid legacy type "%s", known types: "%s"',
                    $type,
                    \implode('", "', \array_keys($this->typeMap))
                )
            );
        }

        $class = $this->typeMap[$type];

        return new $class($structure, $this->inspector, $this->propertyFactory);
    }
}
