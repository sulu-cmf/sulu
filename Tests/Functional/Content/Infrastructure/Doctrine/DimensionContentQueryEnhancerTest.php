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

use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateExampleTrait;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\ProfilerHelperTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class DimensionContentQueryEnhancerTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateTagTrait;
    use ProfilerHelperTrait;

    /**
     * @var ExampleRepository
     */
    private $exampleRepository;

    private ContentManagerInterface $contentManager;

    protected function setUp(): void
    {
        $this->exampleRepository = static::getContainer()->get('example_test.example_repository');
        $this->contentManager = static::getContainer()->get('sulu_content.content_manager');
    }

    public function testNullDimensionAttribute(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live']);
        static::createExampleContent($example2, ['title' => 'Example B']);
        static::createExampleContent($example3, ['title' => 'Example C']);
        static::createExampleContent($example3, ['title' => 'Example C', 'stage' => 'live']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $examples = \iterator_to_array($this->exampleRepository->findBy(['locale' => null, 'stage' => 'draft']));
        $this->assertCount(3, $examples);
    }

    public function testInvalidOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        \iterator_to_array($this->exampleRepository->findBy(['locale' => 'en', 'stage' => 'live', 'tagNames' => ['A', 'B'], 'tagOperator' => 'INVALID'])); // @phpstan-ignore-line
    }

    public function testFindByLocaleAndStage(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live']);
        static::createExampleContent($example2, ['title' => 'Example B']);
        static::createExampleContent($example3, ['title' => 'Example C']);
        static::createExampleContent($example3, ['title' => 'Example C', 'stage' => 'live']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $examples = \iterator_to_array($this->exampleRepository->findBy(['locale' => 'en', 'stage' => 'live']));
        $this->assertCount(2, $examples);
    }

    public function testFindByGhostLocaleAndStage(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live']);
        static::createExampleContent($example2, ['title' => 'Example B']);
        static::createExampleContent($example3, ['title' => 'Example C']);
        static::createExampleContent($example3, ['title' => 'Example C', 'stage' => 'live']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $examples = \iterator_to_array($this->exampleRepository->findBy(['locale' => 'de', 'stage' => 'live']));
        $this->assertCount(0, $examples);

        $examples = \iterator_to_array($this->exampleRepository->findBy([
            'loadGhost' => true,
            'locale' => 'de',
            'stage' => 'live',
        ]));

        $this->assertCount(2, $examples);
    }

    public function testGroupContentAdmin(): void
    {
        $example = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'draft']);
        $tagA = static::createTag(['name' => 'Tag A']);
        $tagB = static::createTag(['name' => 'Tag B']);
        $categoryA = static::createCategory(['key' => 'category_a']);
        static::createCategoryTranslation($categoryA, ['title' => 'Category A']);
        $categoryB = static::createCategory(['key' => 'category_b']);
        static::createCategoryTranslation($categoryA, ['title' => 'Category B']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live', 'excerptTags' => [$tagA, $tagB], 'excerptCategories' => [$categoryA, $categoryB]]);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        static::getEntityManager()->clear();

        $dbDataCollector = static::getDbDataCollector(true);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'live'];
        $example = $this->exampleRepository->getOneBy(
            \array_merge($dimensionAttributes, ['id' => $exampleId]),
            [ExampleRepository::GROUP_SELECT_EXAMPLE_ADMIN => true]
        );
        $dimensionContentCollection = new DimensionContentCollection(
            \iterator_to_array($example->getDimensionContents()),
            $dimensionAttributes,
            ExampleDimensionContent::class
        );
        /** @var ExampleDimensionContent $localizeDimensionContent */
        $localizeDimensionContent = $dimensionContentCollection->getDimensionContent($dimensionAttributes);
        $tagNames = $localizeDimensionContent->getExcerptTagNames();
        $categoryIds = $localizeDimensionContent->getExcerptCategoryIds();

        static::collectDataCollector($dbDataCollector);
        $this->assertSame(1, $dbDataCollector->getQueryCount());
        $this->assertSame($exampleId, $example->getId());
        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(['Tag A', 'Tag B'], $tagNames);
        $this->assertCount(2, $categoryIds);
    }

    public function testGroupContentWebsite(): void
    {
        $example = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'draft']);
        $tagC = static::createTag(['name' => 'Tag C']);
        $tagD = static::createTag(['name' => 'Tag D']);
        $categoryC = static::createCategory(['key' => 'category_c']);
        static::createCategoryTranslation($categoryC, ['title' => 'Category C']);
        $categoryD = static::createCategory(['key' => 'category_d']);
        static::createCategoryTranslation($categoryD, ['title' => 'Category D']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live', 'excerptTags' => [$tagC, $tagD], 'excerptCategories' => [$categoryC, $categoryD]]);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        static::getEntityManager()->clear();

        $dbDataCollector = static::getDbDataCollector(true);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'live'];
        $example = $this->exampleRepository->getOneBy(
            \array_merge($dimensionAttributes, ['id' => $exampleId]),
            [ExampleRepository::GROUP_SELECT_EXAMPLE_WEBSITE => true]
        );
        $dimensionContentCollection = new DimensionContentCollection(
            \iterator_to_array($example->getDimensionContents()),
            $dimensionAttributes,
            ExampleDimensionContent::class
        );
        /** @var ExampleDimensionContent $localizeDimensionContent */
        $localizeDimensionContent = $dimensionContentCollection->getDimensionContent($dimensionAttributes);
        $tagNames = $localizeDimensionContent->getExcerptTagNames();
        $categoryIds = $localizeDimensionContent->getExcerptCategoryIds();

        static::collectDataCollector($dbDataCollector);
        $this->assertSame(1, $dbDataCollector->getQueryCount());
        $this->assertSame($exampleId, $example->getId());
        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(['Tag C', 'Tag D'], $tagNames);
        $this->assertCount(2, $categoryIds);
    }

    public function testGroupContentAdminDisabledSelect(): void
    {
        $example = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'draft']);
        static::createExampleContent($example, ['title' => 'Example A', 'stage' => 'live']);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        static::getEntityManager()->clear();

        $dbDataCollector = static::getDbDataCollector(true);
        $dimensionAttributes = ['locale' => null, 'stage' => 'live'];
        $example = $this->exampleRepository->getOneBy(
            \array_merge($dimensionAttributes, ['id' => $exampleId]),
            [
                ExampleRepository::GROUP_SELECT_EXAMPLE_ADMIN => true,
                ExampleRepository::GROUP_SELECT_EXAMPLE_WEBSITE => false,
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => false,
                ],
            ]
        );
        $dimensionContentCollection = new DimensionContentCollection(
            \iterator_to_array($example->getDimensionContents()),
            $dimensionAttributes,
            ExampleDimensionContent::class
        );
        /** @var ExampleDimensionContent $dimensionContent */
        $dimensionContent = $dimensionContentCollection->getDimensionContent($dimensionAttributes);

        static::collectDataCollector($dbDataCollector);
        $this->assertSame(1, $dbDataCollector->getQueryCount());
        $this->assertSame($exampleId, $example->getId());
        $this->assertCount(1, $dimensionContentCollection);
    }

    public function testCategoryFilters(): void
    {
        static::purgeDatabase();

        $categoryA = static::createCategory(['key' => 'a']);
        $categoryB = static::createCategory(['key' => 'b']);

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A', 'excerptCategories' => [$categoryA]]);
        static::createExampleContent($example2, ['title' => 'Example B']);
        static::createExampleContent($example3, ['title' => 'Example C', 'excerptCategories' => [$categoryA, $categoryB]]);
        static::getEntityManager()->flush();
        $categoryAId = $categoryA->getId();
        $categoryBId = $categoryB->getId();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
        ])));

        $this->assertSame(2, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
        ]));

        $this->assertCount(1, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
            'categoryOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
            'categoryOperator' => 'AND',
        ]));

        $this->assertCount(2, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
        ])));

        $this->assertSame(2, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
        ]));

        $this->assertCount(1, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
            'categoryOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
            'categoryOperator' => 'AND',
        ]));
    }

    public function testTagFilters(): void
    {
        static::purgeDatabase();

        $tagA = static::createTag(['name' => 'a']);
        $tagB = static::createTag(['name' => 'b']);

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A', 'excerptTags' => [$tagA]]);
        static::createExampleContent($example2, ['title' => 'Example B']);
        static::createExampleContent($example3, ['title' => 'Example C', 'excerptTags' => [$tagA, $tagB]]);
        static::getEntityManager()->flush();
        $tagAId = $tagA->getId();
        $tagBId = $tagB->getId();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
        ])));

        $this->assertSame(2, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
        ]));

        $this->assertCount(1, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
            'tagOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
            'tagOperator' => 'AND',
        ]));

        $this->assertCount(2, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
        ])));

        $this->assertSame(2, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
        ]));

        $this->assertCount(1, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
            'tagOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
            'tagOperator' => 'AND',
        ]));
    }

    public function testFilterTemplateKeys(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A', 'templateKey' => 'a']);
        static::createExampleContent($example2, ['title' => 'Example B', 'templateKey' => 'b']);
        static::createExampleContent($example3, ['title' => 'Example C', 'templateKey' => 'c']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->exampleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'templateKeys' => ['a', 'c'],
        ])));

        $this->assertSame(2, $this->exampleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'templateKeys' => ['a', 'c'],
        ]));
    }

    public function testSortByInvalidField(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['title' => 'Example A', 'templateKey' => 'a']);
        static::createExampleContent($example2, ['title' => 'Example B', 'templateKey' => 'b']);
        static::createExampleContent($example3, ['title' => 'Example C', 'templateKey' => 'c']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $unsortedResult = \iterator_to_array($this->exampleRepository->findBy(
            [
                'locale' => 'en',
                'stage' => 'draft',
            ]
        ));

        foreach ($this->exampleRepository->findBy(['locale' => 'en', 'stage' => 'draft'], ['invalid' => 'asc']) as $key => $example) {
            self::assertSame($unsortedResult[$key]->getId(), $example->getId());
        }

        foreach ($this->exampleRepository->findBy(['locale' => 'en', 'stage' => 'draft'], ['invalid' => 'desc']) as $key => $example) {
            self::assertSame($unsortedResult[$key]->getId(), $example->getId());
        }
    }

    public function testSortByTitle(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['templateData' => ['title' => 'Example A'], 'templateKey' => 'a']);
        static::createExampleContent($example2, ['templateData' => ['title' => 'Example B'], 'templateKey' => 'b']);
        static::createExampleContent($example3, ['templateData' => ['title' => 'Example C'], 'templateKey' => 'c']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'title' => 'asc',
                ]
            )
        );
        $this->assertCount(3, $result);
        $this->assertSame('Example A', $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);
        $this->assertSame('Example B', $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);
        $this->assertSame('Example C', $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'title' => 'desc',
                ]
            )
        );
        $this->assertCount(3, $result);
        $this->assertSame('Example C', $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);
        $this->assertSame('Example B', $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);
        $this->assertSame('Example A', $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft'])->getTemplateData()['title']);
    }

    public function testSortByAuthored(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['templateData' => ['title' => 'Example A'], 'authored' => new \DateTimeImmutable('2020-01-01')]);
        static::createExampleContent($example2, ['templateData' => ['title' => 'Example B'], 'authored' => new \DateTimeImmutable('2020-03-01')]);
        static::createExampleContent($example3, ['templateData' => ['title' => 'Example C'], 'authored' => new \DateTimeImmutable('2020-02-01')]);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'authored' => 'desc',
                ]
            )
        );
        $this->assertCount(3, $result);
        /** @var ExampleDimensionContent $exampleDimensionContent */
        $exampleDimensionContent = $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-03-01', $exampleDimensionContent->getAuthored()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent2 */
        $exampleDimensionContent2 = $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-02-01', $exampleDimensionContent2->getAuthored()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent3 */
        $exampleDimensionContent3 = $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-01-01', $exampleDimensionContent3->getAuthored()?->format('Y-m-d'));

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'authored' => 'asc',
                ]
            )
        );
        $this->assertCount(3, $result);
        /** @var ExampleDimensionContent $exampleDimensionContent */
        $exampleDimensionContent = $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-01-01', $exampleDimensionContent->getAuthored()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent2 */
        $exampleDimensionContent2 = $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-02-01', $exampleDimensionContent2->getAuthored()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent3 */
        $exampleDimensionContent3 = $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-03-01', $exampleDimensionContent3->getAuthored()?->format('Y-m-d'));
    }

    public function testSortByWorkflowPublished(): void
    {
        static::purgeDatabase();

        $example = static::createExample();
        $example2 = static::createExample();
        $example3 = static::createExample();
        static::createExampleContent($example, ['templateData' => ['title' => 'Example A'], 'workflowPublished' => new \DateTimeImmutable('2020-01-01')]);
        static::createExampleContent($example2, ['templateData' => ['title' => 'Example B'], 'workflowPublished' => new \DateTimeImmutable('2020-03-01')]);
        static::createExampleContent($example3, ['templateData' => ['title' => 'Example C'], 'workflowPublished' => new \DateTimeImmutable('2020-02-01')]);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'workflowPublished' => 'desc',
                ]
            )
        );
        $this->assertCount(3, $result);
        /** @var ExampleDimensionContent $exampleDimensionContent */
        $exampleDimensionContent = $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-03-01', $exampleDimensionContent->getWorkflowPublished()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent2 */
        $exampleDimensionContent2 = $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-02-01', $exampleDimensionContent2->getWorkflowPublished()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent3 */
        $exampleDimensionContent3 = $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-01-01', $exampleDimensionContent3->getWorkflowPublished()?->format('Y-m-d'));

        $result = \iterator_to_array(
            $this->exampleRepository->findBy(
                [
                    'locale' => 'en',
                    'stage' => 'draft',
                ],
                [
                    'workflowPublished' => 'asc',
                ]
            )
        );
        $this->assertCount(3, $result);
        /** @var ExampleDimensionContent $exampleDimensionContent */
        $exampleDimensionContent = $this->contentManager->resolve($result[0], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-01-01', $exampleDimensionContent->getWorkflowPublished()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent2 */
        $exampleDimensionContent2 = $this->contentManager->resolve($result[1], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-02-01', $exampleDimensionContent2->getWorkflowPublished()?->format('Y-m-d'));
        /** @var ExampleDimensionContent $exampleDimensionContent3 */
        $exampleDimensionContent3 = $this->contentManager->resolve($result[2], ['locale' => 'en', 'stage' => 'draft']);
        $this->assertSame('2020-03-01', $exampleDimensionContent3->getWorkflowPublished()?->format('Y-m-d'));
    }
}
