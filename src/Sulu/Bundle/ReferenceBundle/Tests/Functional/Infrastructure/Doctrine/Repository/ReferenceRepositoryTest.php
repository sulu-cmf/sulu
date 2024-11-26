<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Uid\Uuid;

class ReferenceRepositoryTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private ReferenceRepositoryInterface $referenceRepository;

    public function setUp(): void
    {
        $this->referenceRepository = self::getContainer()->get(ReferenceRepositoryInterface::class);
    }

    public function testCreateNew(): void
    {
        $reference = $this->referenceRepository->create(
            'media',
            (string) 1,
            'pages',
            '434fb6f3-b64e-45e8-8287-fc9667bd3d50',
            'en',
            'Page Title',
            'default',
            'headerImage',
            ['locale' => 'en']
        );

        static::assertSame('media', $reference->getResourceKey());
        static::assertSame('1', $reference->getResourceId());
        static::assertSame('pages', $reference->getReferenceResourceKey());
        static::assertSame('434fb6f3-b64e-45e8-8287-fc9667bd3d50', $reference->getReferenceResourceId());
        static::assertSame('en', $reference->getReferenceLocale());
        static::assertSame('Page Title', $reference->getReferenceTitle());
        static::assertSame('headerImage', $reference->getReferenceProperty());
        static::assertSame(['locale' => 'en'], $reference->getReferenceRouterAttributes());
    }

    public function testFindOneByNotExist(): void
    {
        $this->assertNull($this->referenceRepository->findOneBy(['id' => 2147483647]));
    }

    public function testFindOneByExist(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $reference = $this->referenceRepository->getOneBy(['id' => $referenceId]);

        $this->assertSame($referenceId, $reference->getId());
    }

    public function testFindFlatByNotExist(): void
    {
        /** @var \Generator $references */
        $references = $this->referenceRepository->findFlatBy(filters: ['id' => 2147483647], fields: ['id', 'referenceResourceKey']);

        self::assertNull($references->current());
    }

    public function testFindFlatByExist(): void
    {
        $reference1 = $this->createReference();
        $this->referenceRepository->add($reference1);
        $this->referenceRepository->flush();

        $referenceId = $reference1->getId();

        /** @var \Generator $references */
        $references = $this->referenceRepository->findFlatBy(filters: ['id' => $referenceId], fields: ['id', 'referenceResourceKey']);

        /** @var array{id: int, referenceResourceKey: string} $reference */
        $reference = $references->current();
        $this->assertSame($referenceId, $reference['id']);
        $this->assertSame('pages', $reference['referenceResourceKey']);
    }

    public function testGetOneByNotExist(): void
    {
        $this->expectException(ReferenceNotFoundException::class);

        $this->referenceRepository->getOneBy(['id' => 2147483647]);
    }

    public function testGetOneByExist(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $reference = $this->referenceRepository->getOneBy(['id' => $referenceId]);

        $this->assertSame($referenceId, $reference->getId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterProvider')]
    public function testFindOneByFilter(string $filterKey): void
    {
        self::purgeDatabase();
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();
        /** @var array{id?: int} $filter */
        $filter = [$filterKey => self::getPrivateProperty($reference, $filterKey)];
        $reference = $this->referenceRepository->findOneBy($filter);

        $this->assertNotNull($reference);
        $this->assertSame($referenceId, $reference->getId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterProvider')]
    public function testGetOneByFilter(string $filterKey): void
    {
        self::purgeDatabase();
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        /** @var array{id?: int} $filter */
        $filter = [$filterKey => self::getPrivateProperty($reference, $filterKey)];
        $reference = $this->referenceRepository->getOneBy($filter);

        $this->assertNotNull($reference);
        $this->assertSame($referenceId, $reference->getId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterProvider')]
    public function testFindFlatFilter(string $filterKey): void
    {
        self::purgeDatabase();
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        /** @var array{id?: int} $filters */
        $filters = [$filterKey => self::getPrivateProperty($reference, $filterKey)];

        /** @var \Generator $references */
        $references = $this->referenceRepository->findFlatBy(filters: $filters, fields: ['id']);

        $this->assertNotNull($references);
        /** @var array{id: int} $reference */
        $reference = $references->current();
        $this->assertSame($referenceId, $reference['id']);
    }

    public function testAddAndRemove(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $referenceId]));

        $this->referenceRepository->remove($reference);
        $this->referenceRepository->flush();

        $this->assertNull($this->referenceRepository->findOneBy(['id' => $referenceId]));
    }

    public function testRemoveBy(): void
    {
        static::purgeDatabase();

        $referenceResourceKey = 'pages';
        $referenceResourceId = Uuid::v7()->__toString();
        $referenceLocale = 'en';

        $reference1 = $this->createReference();
        $reference2 = $this->createReference();
        $reference3 = $this->createReference();
        $reference4 = $this->createReference();
        static::setPrivateProperty($reference2, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference3, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference3, 'referenceLocale', $referenceLocale);
        static::setPrivateProperty($reference4, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference4, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference4, 'referenceLocale', $referenceLocale);

        $this->referenceRepository->add($reference1);
        $this->referenceRepository->add($reference2);
        $this->referenceRepository->add($reference3);
        $this->referenceRepository->add($reference4);

        $this->referenceRepository->flush();
        $reference1Id = $reference1->getId();
        $reference2Id = $reference2->getId();
        $reference3Id = $reference3->getId();
        $reference4Id = $reference4->getId();

        $this->referenceRepository->removeBy([
            'referenceResourceKey' => $referenceResourceKey,
            'referenceResourceId' => $referenceResourceId,
            'referenceLocale' => $referenceLocale,
        ]);

        $this->referenceRepository->flush();

        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $reference1Id]));
        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $reference2Id]));
        $this->assertNull($this->referenceRepository->findOneBy(['id' => $reference3Id]));
        $this->assertNull($this->referenceRepository->findOneBy(['id' => $reference4Id]));
    }

    public function testCount(): void
    {
        static::purgeDatabase();

        $referenceResourceKey = 'article';
        $referenceResourceId = Uuid::v7()->__toString();
        $referenceLocale = 'de';

        $reference1 = $this->createReference();
        $reference2 = $this->createReference();
        $reference3 = $this->createReference();
        $reference4 = $this->createReference();
        static::setPrivateProperty($reference2, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference3, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference3, 'referenceLocale', $referenceLocale);
        static::setPrivateProperty($reference4, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference4, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference4, 'referenceLocale', $referenceLocale);

        $this->referenceRepository->add($reference1);
        $this->referenceRepository->add($reference2);
        $this->referenceRepository->add($reference3);
        $this->referenceRepository->add($reference4);

        $this->referenceRepository->flush();
        $reference1Id = $reference1->getId();
        $reference2Id = $reference2->getId();
        $reference3Id = $reference3->getId();
        $reference4Id = $reference4->getId();

        $this->assertSame(4, $this->referenceRepository->count());
        $this->assertSame(2, $this->referenceRepository->count(['referenceResourceKey' => $referenceResourceKey]));
        $this->assertSame(2, $this->referenceRepository->count(['referenceResourceId' => $referenceResourceId]));
        $this->assertSame(2, $this->referenceRepository->count(['referenceLocale' => $referenceLocale]));
        $this->assertSame(1, $this->referenceRepository->count(['id' => $reference1Id]));
        $this->assertSame(1, $this->referenceRepository->count(['id' => $reference2Id]));
        $this->assertSame(1, $this->referenceRepository->count(['id' => $reference3Id]));
        $this->assertSame(1, $this->referenceRepository->count(['id' => $reference4Id]));
    }

    public function testCountDistinctFields(): void
    {
        static::purgeDatabase();

        $referenceResourceKey = 'article';
        $referenceResourceId = Uuid::v7()->__toString();
        $referenceLocale = 'de';

        $reference1 = $this->createReference();
        $reference2 = $this->createReference();
        $reference3 = $this->createReference();
        $reference4 = $this->createReference();
        static::setPrivateProperty($reference2, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference3, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference3, 'referenceLocale', $referenceLocale);
        static::setPrivateProperty($reference4, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference4, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference4, 'referenceLocale', $referenceLocale);

        $this->referenceRepository->add($reference1);
        $this->referenceRepository->add($reference2);
        $this->referenceRepository->add($reference3);
        $this->referenceRepository->add($reference4);

        $this->referenceRepository->flush();

        $this->assertSame(4, $this->referenceRepository->count());
        $this->assertSame(1, $this->referenceRepository->count(['referenceResourceKey' => $referenceResourceKey], ['referenceResourceKey']));
        $this->assertSame(2, $this->referenceRepository->count(['referenceResourceKey' => $referenceResourceKey], ['referenceLocale']));
    }

    private function createReference(): ReferenceInterface
    {
        $reference = $this->referenceRepository->create(
            'media',
            '1',
            'pages',
            Uuid::v7()->__toString(),
            'en',
            'Page Title',
            'default',
            'headerImage',
            ['locale' => 'en']
        );

        return $reference;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function filterProvider(): array
    {
        return [
            ['id'],
            ['resourceKey'],
            ['resourceId'],
            ['referenceResourceKey'],
            ['referenceResourceId'],
            ['referenceLocale'],
            ['referenceTitle'],
            ['referenceContext'],
            ['referenceProperty'],
            ['referenceRouterAttributes'],
        ];
    }
}
