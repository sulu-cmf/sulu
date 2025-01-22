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

namespace Sulu\Content\Infrastructure\Sulu\Structure;

use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

class ContentStructureBridgeFactory
{
    public function __construct(
        protected StructureMetadataFactoryInterface $structureMetadataFactory,
        private LegacyPropertyFactory $propertyFactory,
    ) {
    }

    /**
     * @param string|int $id
     */
    public function getBridge(TemplateInterface $object, $id, string $locale): ContentStructureBridge
    {
        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            $object::getTemplateType(),
            $object->getTemplateKey()
        );

        if (!$structureMetadata) {
            throw new StructureMetadataNotFoundException($object::getTemplateType(), $object->getTemplateKey());
        }

        return new ContentStructureBridge(
            $structureMetadata,
            $this->propertyFactory,
            $object,
            $id,
            $locale
        );
    }
}
