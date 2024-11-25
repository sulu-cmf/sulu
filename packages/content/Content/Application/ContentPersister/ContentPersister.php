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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentPersister;

use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentPersister implements ContentPersisterInterface
{
    /**
     * @var DimensionContentCollectionFactoryInterface
     */
    private $dimensionContentCollectionFactory;

    /**
     * @var ContentMergerInterface
     */
    private $contentMerger;

    public function __construct(
        DimensionContentCollectionFactoryInterface $dimensionContentCollectionFactory,
        ContentMergerInterface $contentMerger
    ) {
        $this->dimensionContentCollectionFactory = $dimensionContentCollectionFactory;
        $this->contentMerger = $contentMerger;
    }

    public function persist(ContentRichEntityInterface $contentRichEntity, array $data, array $dimensionAttributes): DimensionContentInterface
    {
        /*
         * Data should always be persisted to the STAGE_DRAFT content-dimension of the given $dimensionAttributes.
         * Modifying data of other content-dimensions (eg. STAGE_LIVE) should only be possible by applying transitions
         * of the ContentWorkflow.
         *
         * TODO: maybe throw an exception here if the $dimensionAttributes contain another stage than 'STAGE_DRAFT'
         */

        $dimensionContentCollection = $this->dimensionContentCollectionFactory->create(
            $contentRichEntity,
            $dimensionAttributes,
            $data
        );

        return $this->contentMerger->merge($dimensionContentCollection);
    }
}
