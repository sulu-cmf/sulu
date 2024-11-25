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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper;

use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;

class ContentDataMapper implements ContentDataMapperInterface
{
    /**
     * @var iterable<DataMapperInterface>
     */
    private $dataMappers;

    /**
     * @param iterable<DataMapperInterface> $dataMappers
     */
    public function __construct(iterable $dataMappers)
    {
        $this->dataMappers = $dataMappers;
    }

    public function map(
        DimensionContentCollectionInterface $dimensionContentCollection,
        array $dimensionAttributes,
        array $data
    ): void {
        $localizedDimensionAttributes = $dimensionAttributes;
        $unlocalizedDimensionAttributes = $dimensionAttributes;
        $unlocalizedDimensionAttributes['locale'] = null;
        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent($unlocalizedDimensionAttributes);
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent($localizedDimensionAttributes);

        if (!$unlocalizedDimensionContent || !$localizedDimensionContent) {
            // TODO see https://github.com/sulu/SuluContentBundle/pull/204
            throw new \RuntimeException('Create unlocalized and localized dimension content.');
        }

        foreach ($this->dataMappers as $mapper) {
            $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
        }
    }
}
