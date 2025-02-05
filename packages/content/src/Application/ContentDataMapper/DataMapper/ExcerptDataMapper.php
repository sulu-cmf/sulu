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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Webmozart\Assert\Assert;

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
     * @param array<string, mixed> $data
     */
    private function setExcerptData(ExcerptInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('excerptTitle', $data)) {
            Assert::nullOrString($data['excerptTitle']);
            $dimensionContent->setExcerptTitle($data['excerptTitle']);
        }
        if (\array_key_exists('excerptDescription', $data)) {
            Assert::nullOrString($data['excerptDescription']);
            $dimensionContent->setExcerptDescription($data['excerptDescription']);
        }
        if (\array_key_exists('excerptMore', $data)) {
            Assert::nullOrString($data['excerptMore']);
            $dimensionContent->setExcerptMore($data['excerptMore']);
        }
        if (\array_key_exists('excerptImage', $data)) {
            Assert::nullOrIsArray($data['excerptImage']);
            Assert::nullOrInteger($data['excerptImage']['id'] ?? null);
            $dimensionContent->setExcerptImage($data['excerptImage']); // @phpstan-ignore argument.type
        }
        if (\array_key_exists('excerptIcon', $data)) {
            Assert::nullOrIsArray($data['excerptIcon']);
            Assert::nullOrInteger($data['excerptIcon']['id'] ?? null);
            $dimensionContent->setExcerptIcon($data['excerptIcon']); // @phpstan-ignore argument.type
        }
        if (\array_key_exists('excerptTags', $data)) {
            Assert::isArray($data['excerptTags']);
            Assert::allString($data['excerptTags']);
            $dimensionContent->setExcerptTags($this->tagFactory->create($data['excerptTags']));
        }
        if (\array_key_exists('excerptCategories', $data)) {
            Assert::isArray($data['excerptCategories']);
            Assert::allInteger($data['excerptCategories']);
            $dimensionContent->setExcerptCategories(
                $this->categoryFactory->create($data['excerptCategories'])
            );
        }
    }
}
