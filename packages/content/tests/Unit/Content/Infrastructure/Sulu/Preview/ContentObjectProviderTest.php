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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Preview;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Content\Infrastructure\Sulu\Preview\PreviewDimensionContentCollection;
use Sulu\Content\Tests\Application\ExampleTestBundle\Admin\ExampleAdmin;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentObjectProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private $contentAggregator;

    /**
     * @var ObjectProphecy<ContentDataMapperInterface>
     */
    private $contentDataMapper;

    /**
     * @var ContentObjectProvider<ExampleDimensionContent, Example>
     */
    private $contentObjectProvider;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);

        $this->contentObjectProvider = new ContentObjectProvider(
            $this->entityManager->reveal(),
            $this->contentAggregator->reveal(),
            $this->contentDataMapper->reveal(),
            Example::class,
            ExampleAdmin::SECURITY_CONTEXT
        );
    }

    public function testGetObject(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willReturn($dimensionContent->reveal())->shouldBeCalledTimes(1);

        $result = $this->contentObjectProvider->getObject((string) $id, $locale);

        $this->assertSame($dimensionContent->reveal(), $result);
    }

    public function testGetObjectWithShadow(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContent->getShadowLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'de',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($dimensionContent->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn('en');
        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($dimensionContent->reveal())->shouldBeCalledTimes(1);

        $result = $this->contentObjectProvider->getObject((string) $id, $locale);

        $this->assertSame($dimensionContent->reveal(), $result);
    }

    public function testGetNonExistingObject(int $id = 1, string $locale = 'de'): void
    {
        $this->entityManager->createQueryBuilder()->willThrow(NoResultException::class)->shouldBeCalledTimes(1);

        $result = $this->contentObjectProvider->getObject((string) $id, $locale);

        $this->assertNull($result);
    }

    public function testGetObjectContentNotFound(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willThrow(ContentNotFoundException::class)->shouldBeCalledTimes(1);

        $result = $this->contentObjectProvider->getObject((string) $id, $locale);

        $this->assertNull($result);
    }

    public function testGetId(int $id = 1): void
    {
        $contentRichEntity = new Example();
        static::setPrivateProperty($contentRichEntity, 'id', $id);

        $dimensionContent = new ExampleDimensionContent($contentRichEntity);

        $actualId = (string) $this->contentObjectProvider->getId($dimensionContent);

        $this->assertSame((string) $id, $actualId);
    }

    /**
     * @param mixed[] $data
     */
    public function testSetValues(
        string $locale = 'de',
        array $data = [
            'title' => 'Title',
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keywords',
            'seoCanonicalUrl' => 'Seo Canonical Url',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['foo', 'bar'],
            'excerptCategories' => [1, 2],
            'excerptImage' => ['id' => 3],
            'excerptIcon' => ['id' => 4],
        ]
    ): void {
        $example = new Example();
        $exampleDimensionContent = new ExampleDimensionContent($example);

        $this->contentObjectProvider->setValues($exampleDimensionContent, $locale, $data);

        $this->contentDataMapper->map(
            Argument::that(
                function(PreviewDimensionContentCollection $dimensionContentCollection) use ($exampleDimensionContent) {
                    return $exampleDimensionContent === $dimensionContentCollection->getDimensionContent([]);
                }
            ),
            ['locale' => 'de', 'stage' => 'draft'],
            $data
        )->shouldBeCalledTimes(1);
    }

    /**
     * @param mixed[] $context
     */
    public function testSetContext(string $locale = 'de', array $context = ['template' => 'overview']): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $this->contentObjectProvider->setContext($dimensionContent, $locale, $context);

        $this->assertSame($context['template'], $dimensionContent->getTemplateKey());
    }

    public function testSerialize(): void
    {
        $contentRichEntity = new Example();
        static::setPrivateProperty($contentRichEntity, 'id', '123-456');

        $dimensionContent = new ExampleDimensionContent($contentRichEntity);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage('draft');

        $serializedObject = \json_encode([
            'id' => '123-456',
            'locale' => 'en',
        ]);

        $result = $this->contentObjectProvider->serialize($dimensionContent);

        $this->assertSame($serializedObject, $result);
    }

    public function testDeserialize(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = new ExampleDimensionContent(new Example());

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willReturn($dimensionContent)->shouldBeCalledTimes(1);

        $serializedObject = \json_encode([
            'id' => '123-456',
            'locale' => 'en',
        ]) ?: '';

        $result = $this->contentObjectProvider->deserialize($serializedObject, DimensionContentInterface::class);

        $this->assertSame($dimensionContent, $result);
    }

    public function testDeserializeIdNull(): void
    {
        $serializedObject = \json_encode([
            'id' => null,
            'locale' => 'en',
        ]) ?: '';

        $result = $this->contentObjectProvider->deserialize($serializedObject, DimensionContentInterface::class);

        $this->assertNull($result);
    }

    public function testDeserializeLocaleNull(): void
    {
        $serializedObject = \json_encode([
            'id' => '123-456',
            'locale' => null,
        ]) ?: '';

        $result = $this->contentObjectProvider->deserialize($serializedObject, DimensionContentInterface::class);

        $this->assertNull($result);
    }

    public function testGetSecurityContext(): void
    {
        $this->assertSame(
            ExampleAdmin::SECURITY_CONTEXT,
            $this->contentObjectProvider->getSecurityContext('123-123-123', 'en')
        );
    }
}
