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

namespace Sulu\Content\Application\ContentPersister;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentPersisterInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param array<string, mixed> $data
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function persist(ContentRichEntityInterface $contentRichEntity, array $data, array $dimensionAttributes): DimensionContentInterface;
}
