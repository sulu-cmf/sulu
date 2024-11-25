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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\ContentBundle\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;

class ExcerptDataMapper implements DataMapperInterface
{
    /**
     * @var TagFactoryInterface
     */
    private $tagFactory;

    /**
     * @var CategoryFactoryInterface
     */
    private $categoryFactory;

    public function __construct(TagFactoryInterface $tagFactory, CategoryFactoryInterface $categoryFactory)
    {
        $this->tagFactory = $tagFactory;
        $this->categoryFactory = $categoryFactory;
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof ExcerptInterface) {
            return;
        }

        $this->setExcerptData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setExcerptData(ExcerptInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('excerptTitle', $data)) {
            $dimensionContent->setExcerptTitle($data['excerptTitle']);
        }
        if (\array_key_exists('excerptDescription', $data)) {
            $dimensionContent->setExcerptDescription($data['excerptDescription']);
        }
        if (\array_key_exists('excerptMore', $data)) {
            $dimensionContent->setExcerptMore($data['excerptMore']);
        }
        if (\array_key_exists('excerptImage', $data)) {
            $dimensionContent->setExcerptImage($data['excerptImage']);
        }
        if (\array_key_exists('excerptIcon', $data)) {
            $dimensionContent->setExcerptIcon($data['excerptIcon']);
        }
        if (\array_key_exists('excerptTags', $data)) {
            $dimensionContent->setExcerptTags($this->tagFactory->create($data['excerptTags'] ?: []));
        }
        if (\array_key_exists('excerptCategories', $data)) {
            $dimensionContent->setExcerptCategories(
                $this->categoryFactory->create($data['excerptCategories'] ?: [])
            );
        }
    }
}
