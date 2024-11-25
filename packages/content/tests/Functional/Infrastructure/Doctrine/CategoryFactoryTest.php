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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Factory\CategoryFactoryInterface;

class CategoryFactoryTest extends SuluTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
    }

    public function createCategoryFactory(): CategoryFactoryInterface
    {
        return self::getContainer()->get('sulu_content.category_factory');
    }

    /**
     * @param int[] $categoryIds
     */
    #[DataProvider('dataProvider')]
    public function testCreate(array $categoryIds): void
    {
        $categoryFactory = $this->createCategoryFactory();

        $this->assertSame(
            $categoryIds,
            \array_map(
                function(CategoryInterface $category) {
                    return $category->getId();
                },
                $categoryFactory->create($categoryIds)
            )
        );
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function dataProvider(): \Generator
    {
        yield [
            [
                // No categories
            ],
        ];

        yield [
            [
                1,
                2,
            ],
        ];
    }
}
