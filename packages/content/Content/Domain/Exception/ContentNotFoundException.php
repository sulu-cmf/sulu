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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Exception;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentNotFoundException extends \Exception
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     */
    public function __construct(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes)
    {
        parent::__construct(\sprintf(
            'Could not load content with id "%s" and attributes: %s',
            $contentRichEntity->getId(),
            \json_encode($dimensionAttributes)
        ));
    }
}
