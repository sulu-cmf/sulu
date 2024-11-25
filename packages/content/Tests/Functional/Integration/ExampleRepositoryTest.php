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

use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Exception\ExampleNotFoundException;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateExampleTrait;
use Sulu\Bundle\ContentBundle\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * This test the functionality which is required to work for the `DimensionContentQueryEnhancerTest`.
 */
class ExampleRepositoryTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateTagTrait;

    /**
     * @var ExampleRepository
     */
    private $exampleRepository;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
    }

    protected function setUp(): void
    {
        $this->exampleRepository = static::getContainer()->get('example_test.example_repository');
    }

    public function testFindOneByNotExist(): void
    {
        $this->assertNull($this->exampleRepository->findOneBy(['id' => \PHP_INT_MAX]));
    }

    public function testGetOneByNotExist(): void
    {
        $this->expectException(ExampleNotFoundException::class);

        $this->exampleRepository->getOneBy(['id' => \PHP_INT_MAX]);
    }

    public function testFindByNotExist(): void
    {
        $examples = \iterator_to_array($this->exampleRepository->findBy(['ids' => [\PHP_INT_MAX]]));
        $this->assertCount(0, $examples);
    }

    public function testAdd(): void
    {
        $example = new Example();

        $this->exampleRepository->add($example);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        static::getEntityManager()->clear();

        $example = $this->exampleRepository->getOneBy(['id' => $exampleId]);
        $this->assertSame($exampleId, $example->getId());
    }

    public function testRemove(): void
    {
        $example = new Example();

        $this->exampleRepository->add($example);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        static::getEntityManager()->clear();

        $example = $this->exampleRepository->getOneBy(['id' => $exampleId]);
        $this->exampleRepository->remove($example);
        static::getEntityManager()->flush();

        $this->assertNull($this->exampleRepository->findOneBy(['id' => $exampleId]));
    }

    public function testCountBy(): void
    {
        static::purgeDatabase();

        $this->exampleRepository->add(new Example());
        $this->exampleRepository->add(new Example());
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $this->assertSame(2, $this->exampleRepository->countBy());
    }

    public function testFindByIds(): void
    {
        static::purgeDatabase();

        $example = new Example();
        $example2 = new Example();
        $example3 = new Example();

        $this->exampleRepository->add($example);
        $this->exampleRepository->add($example2);
        $this->exampleRepository->add($example3);
        static::getEntityManager()->flush();
        $exampleId = $example->getId();
        $example3Id = $example3->getId();
        static::getEntityManager()->clear();

        $examples = \iterator_to_array($this->exampleRepository->findBy(['ids' => [$exampleId, $example3Id]]));

        $this->assertCount(2, $examples);
    }

    public function testFindByLimitAndPage(): void
    {
        static::purgeDatabase();

        $this->exampleRepository->add(new Example());
        $this->exampleRepository->add(new Example());
        $this->exampleRepository->add(new Example());
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $examples = \iterator_to_array($this->exampleRepository->findBy(['limit' => 2, 'page' => 2]));
        $this->assertCount(1, $examples);
    }
}
