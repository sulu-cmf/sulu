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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class ContentStructureBridgeFactory
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureMetadataFactory;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    public function __construct(StructureMetadataFactoryInterface $structureMetadataFactory, LegacyPropertyFactory $propertyFactory)
    {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->propertyFactory = $propertyFactory;
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
