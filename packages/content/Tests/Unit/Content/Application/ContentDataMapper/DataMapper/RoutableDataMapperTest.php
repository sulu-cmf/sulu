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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\RoutableDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\ContentBundle\Tests\Traits\SetGetPrivatePropertyTrait;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class RoutableDataMapperTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private $routeGenerator;

    /**
     * @var ObjectProphecy<RouteManagerInterface>
     */
    private $routeManager;

    /**
     * @var ObjectProphecy<ConflictResolverInterface>
     */
    private $conflictResolver;

    protected function setUp(): void
    {
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);
    }

    /**
     * @param array<string, array<mixed>> $resourceKeyMappings
     */
    protected function createRouteDataMapperInstance(
        ?array $resourceKeyMappings = null
    ): RoutableDataMapper {
        if (!\is_array($resourceKeyMappings)) {
            $resourceKeyMappings = [
                'examples' => [
                    'generator' => 'schema',
                    'options' => [
                        'route_schema' => '/{object["title"]}',
                    ],
                    'resource_key' => 'examples',
                    'entityClass' => Example::class,
                ],
            ];
        }

        return new RoutableDataMapper(
            $this->structureMetadataFactory->reveal(),
            $this->routeGenerator->reveal(),
            $this->routeManager->reveal(),
            $this->conflictResolver->reveal(),
            $resourceKeyMappings
        );
    }

    public function testMapNoRoutableInterface(): void
    {
        $data = [];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoTemplateInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LocalizedDimensionContent needs to extend the TemplateInterface.');

        $data = [];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $unlocalizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoTemplateGiven(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LocalizedDimensionContent should return the a template.');

        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance([]);

        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoMetadata(): void
    {
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn(null);
        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoRouteProperty(): void
    {
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createTextLineStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoLocale(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a LocalizedDimensionContent with a locale.');

        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoRoutePropertyValue(): void
    {
        // see https://github.com/sulu/SuluContentBundle/pull/55
        $this->markTestSkipped(
            'This is currently handled the same way as "testMapNoNewAndOldUrl". But should be handle differently to avoid patch creates.'
        );

        /** @phpstan-ignore-next-line */
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoRouteMapping(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No route mapping found for "examples".');

        $data = [
            'url' => '/test',
        ];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance([]);
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoResourceId(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a LocalizedDimensionContent with a resourceId.');

        $data = [
            'url' => '/test',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapRouteProperty(): void
    {
        $data = [
            'url' => '/test',
        ];

        $route = new Route();
        $route->setPath('/test-1');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(
            Example::class,
            '1',
            'en',
            '/test',
            false
        )
            ->shouldBeCalled()
            ->willReturn($route);

        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'url' => '/test-1',
        ], $localizedDimensionContent->getTemplateData());
    }

    public function testMapRoutePropertyFalseName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a property with the name "url" but "route" given.');

        $data = [
            'route' => '/test',
        ];

        $route = new Route();
        $route->setPath('/test-1');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata('route'));

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }

    public function testMapRouteDraftDimension(): void
    {
        $data = [
            'url' => '/test',
        ];

        $conflictRoute = new Route();
        $conflictRoute->setPath('/test-1');

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $this->conflictResolver->resolve(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($conflictRoute);

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'url' => '/test-1',
        ], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoNewAndOldUrl(): void
    {
        $data = [];

        $route = new Route();
        $route->setPath('/test');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeGenerator->generate(Argument::cetera())->willReturn('/test');

        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $this->routeManager->createOrUpdateByAttributes(
            Example::class,
            '1',
            'en',
            '/test',
            false
        )
            ->shouldBeCalled()
            ->willReturn($route);

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'url' => '/test',
        ], $localizedDimensionContent->getTemplateData());
    }

    public function testMapWithNoUrlButOldUrl(): void
    {
        $data = [];

        $route = new Route();
        $route->setPath('/test-1');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setTemplateData(['url' => '/example']);

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'url' => '/example',
        ], $localizedDimensionContent->getTemplateData());
    }

    public function testMapGenerate(): void
    {
        $data = [
            'url' => null,
        ];

        $route = new Route();
        $route->setPath('/custom/testEntity-123');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 123);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setTemplateData(['title' => 'Test', 'url' => null]);

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());

        $this->routeGenerator->generate(
            \array_merge($data, [
                '_unlocalizedObject' => $unlocalizedDimensionContent,
                '_localizedObject' => $localizedDimensionContent,
            ]),
            ['route_schema' => 'custom/{object["_localizedObject"].getTitle()}-{object["_unlocalizedObject"].getResourceId()}']
        )->willReturn('/custom/testEntity-123');

        $this->routeManager->createOrUpdateByAttributes(
            Example::class,
            '123',
            'en',
            '/custom/testEntity-123',
            false
        )
            ->shouldBeCalled()
            ->willReturn($route);

        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance([
            'examples' => [
                'generator' => 'schema',
                'options' => [
                    'route_schema' => 'custom/{object["_localizedObject"].getTitle()}-{object["_unlocalizedObject"].getResourceId()}',
                ],
                'resource_key' => 'examples',
                'entityClass' => Example::class,
            ],
        ]);
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'title' => 'Test',
            'url' => '/custom/testEntity-123',
        ], $localizedDimensionContent->getTemplateData());
    }

    public function testMapOnlySlash(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not allowed url "/" given or generated.');

        $data = [
            'url' => null,
        ];

        $route = new Route();
        $route->setPath('/custom/testEntity-123');

        $example = new Example();
        static::setPrivateProperty($example, 'id', 123);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setTemplateData(['title' => 'Test', 'url' => null]);

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeGenerator->generate(Argument::cetera())->willReturn('/');

        $this->conflictResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'url' => '/custom/testEntity-123',
            'title' => 'Test',
        ], $localizedDimensionContent->getTemplateData());
    }

    private function createRouteStructureMetadata(string $propertyName = 'url'): StructureMetadata
    {
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn($propertyName);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getProperties()->willReturn([
            $property->reveal(),
        ])->shouldBeCalled();

        return $structureMetadata->reveal();
    }

    private function createTextLineStructureMetadata(): StructureMetadata
    {
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('text_line');
        $property->getName()->willReturn('url');

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getProperties()->willReturn([
            $property->reveal(),
        ])->shouldBeCalled();

        return $structureMetadata->reveal();
    }
}
