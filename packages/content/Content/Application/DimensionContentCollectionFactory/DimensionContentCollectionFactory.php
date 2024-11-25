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

namespace Sulu\Bundle\ContentBundle\Content\Application\DimensionContentCollectionFactory;

use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DimensionContentCollectionFactory implements DimensionContentCollectionFactoryInterface
{
    /**
     * @var DimensionContentRepositoryInterface
     */
    private $dimensionContentRepository;

    /**
     * @var ContentDataMapperInterface
     */
    private $contentDataMapper;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(
        DimensionContentRepositoryInterface $dimensionContentRepository,
        ContentDataMapperInterface $contentDataMapper,
        PropertyAccessor $propertyAccessor
    ) {
        $this->dimensionContentRepository = $dimensionContentRepository;
        $this->contentDataMapper = $contentDataMapper;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function create(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        array $data
    ): DimensionContentCollectionInterface {
        $dimensionContentCollection = $this->dimensionContentRepository->load($contentRichEntity, $dimensionAttributes);
        $dimensionAttributes = $dimensionContentCollection->getDimensionAttributes();

        $unlocalizedAttributes = $dimensionAttributes;
        $unlocalizedAttributes['locale'] = null;

        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent($unlocalizedAttributes);

        if (!$unlocalizedDimensionContent) {
            $unlocalizedDimensionContent = $this->createContentDimension(
                $contentRichEntity,
                $unlocalizedAttributes
            );
        }

        $localizedDimensionContent = null;
        /** @var string|null $locale */
        $locale = $dimensionAttributes['locale'] ?? null;
        if ($locale) {
            $localizedDimensionContent = $dimensionContentCollection->getDimensionContent($dimensionAttributes);

            if (!$localizedDimensionContent) {
                $localizedDimensionContent = $this->createContentDimension(
                    $contentRichEntity,
                    $dimensionAttributes
                );

                $unlocalizedDimensionContent->addAvailableLocale($locale);

                if (!$unlocalizedDimensionContent->getGhostLocale()) {
                    $unlocalizedDimensionContent->setGhostLocale($locale);
                }
            }
        }

        $dimensionContentCollection = new DimensionContentCollection(
            \array_filter([
                $unlocalizedDimensionContent,
                $localizedDimensionContent,
            ]),
            $dimensionAttributes,
            $dimensionContentCollection->getDimensionContentClass()
        );

        $this->contentDataMapper->map($dimensionContentCollection, $dimensionAttributes, $data);

        return $dimensionContentCollection;
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $attributes
     *
     * @return T
     */
    private function createContentDimension(
        ContentRichEntityInterface $contentRichEntity,
        array $attributes
    ): DimensionContentInterface {
        $dimensionContent = $contentRichEntity->createDimensionContent();

        foreach ($attributes as $attributeName => $attributeValue) {
            $this->propertyAccessor->setValue($dimensionContent, $attributeName, $attributeValue);
        }

        $contentRichEntity->addDimensionContent($dimensionContent);

        return $dimensionContent;
    }
}
