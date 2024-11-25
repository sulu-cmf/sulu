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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

interface ContentWorkflowInterface
{
    public const CONTENT_RICH_ENTITY_CONTEXT_KEY = 'contentRichEntity';
    public const DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY = 'dimensionContentCollection';
    public const DIMENSION_ATTRIBUTES_CONTEXT_KEY = 'dimensionAttributes';

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function apply(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        string $transitionName
    ): DimensionContentInterface;
}
