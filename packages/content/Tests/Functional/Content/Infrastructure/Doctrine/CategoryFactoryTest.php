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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Doctrine;

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

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
     * @dataProvider dataProvider
     *
     * @param int[] $categoryIds
     */
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
    public function dataProvider(): \Generator
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
