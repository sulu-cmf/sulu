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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Route;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Route\ContentRouteDefaultsProvider;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentStructureBridge;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentStructureBridgeFactory;
use Sulu\Content\Infrastructure\Sulu\Structure\StructureMetadataNotFoundException;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Webmozart\Assert\Assert;

class ContentRouteDefaultsProviderTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getContentRouteDefaultsProvider(
        EntityManagerInterface $entityManager,
        ContentAggregatorInterface $contentAggregator,
        ContentStructureBridgeFactory $contentStructureBridgeFactory,
        CacheLifetimeResolverInterface $cacheLifetimeResolver
    ): ContentRouteDefaultsProvider {
        return new ContentRouteDefaultsProvider(
            $entityManager,
            $contentAggregator,
            $contentStructureBridgeFactory,
            $cacheLifetimeResolver
        );
    }

    public function testSupports(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();

        $this->assertTrue($contentRouteDefaultsProvider->supports($contentRichEntity::class));
        $this->assertFalse($contentRouteDefaultsProvider->supports(\stdClass::class));
    }

    public function testIsPublished(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setStage('live');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentAggregator->aggregate(
            $contentRichEntity,
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($resolvedDimensionContent);

        $this->assertTrue($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedEntityNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willThrow(new NoResultException());

        $contentAggregator->aggregate(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedContentNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentAggregator->aggregate(
            $contentRichEntity,
            ['locale' => 'en', 'stage' => 'live']
        )->willThrow(new ContentNotFoundException($contentRichEntity, ['locale' => 'en', 'stage' => 'live']));

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedWithLocalizedDimension(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setStage('live');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent)
            ->shouldBeCalled();

        $this->assertTrue($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedWithUnlocalizedDimension(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale(null);
        $resolvedDimensionContent->setStage('live');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent)
            ->shouldBeCalled();

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testGetByEntityReturnNoneTemplate(): void
    {
        $resolvedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($resolvedDimensionContent->reveal())
        ));

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentAggregator->aggregate(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($resolvedDimensionContent->reveal());

        $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en');
    }

    public function testGetByEntityReturnNoneTemplateFromPreview(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($dimensionContent->reveal())
        ));

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentAggregator->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en', $dimensionContent->reveal());
    }

    public function testGetByEntity(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentResolver->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $cacheLifetimeResolver->supports('seconds', 3600)->willReturn(true);
        $cacheLifetimeResolver->resolve('seconds', 3600)->willReturn(3600);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getCacheLifetime()->willReturn(['value' => 3600, 'type' => 'seconds']);

        $contentStructureBridge = $this->prophesize(ContentStructureBridge::class);
        $contentStructureBridge->getView()->willReturn('default');
        $contentStructureBridge->getController()->willReturn('App\Controller\TestController:testAction');
        $contentStructureBridge->getStructure()->willReturn($structureMetadata->reveal());
        $contentStructureBridgeFactory->getBridge($resolvedDimensionContent, '123-123-123', 'en')
            ->willReturn($contentStructureBridge->reveal());

        $result = $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en');
        $this->assertSame($resolvedDimensionContent, $result['object']);
        $this->assertSame('default', $result['view']);
        $this->assertSame($contentStructureBridge->reveal(), $result['structure']);
        $this->assertSame('App\Controller\TestController:testAction', $result['_controller']);
        $this->assertSame(3600, $result['_cacheLifetime']);
    }

    public function testGetByEntityNotPublished(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentResolver->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->will(function(array $arguments) {
                $entity = $arguments[0] ?? null;
                $attributes = $arguments[1] ?? null;

                Assert::isInstanceOf($entity, ContentRichEntityInterface::class);
                Assert::isArray($attributes);

                throw new ContentNotFoundException($entity, $attributes);
            });

        $this->assertEmpty($contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en'));
    }

    public function testGetByEntityStructureMetadataNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal(),
            $cacheLifetimeResolver->reveal()
        );

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $contentResolver->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $contentStructureBridgeFactory->getBridge($resolvedDimensionContent, '123-123-123', 'en')
            ->willThrow(StructureMetadataNotFoundException::class);

        $this->assertEmpty($contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en'));
    }
}
