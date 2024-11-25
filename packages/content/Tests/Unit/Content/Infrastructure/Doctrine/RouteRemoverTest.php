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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\RouteRemover;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class RouteRemoverTest extends TestCase
{
    use ProphecyTrait;

    public static function getResourceKey(): string
    {
        return 'test';
    }

    /**
     * @var ObjectProphecy<ContentMetadataInspectorInterface>
     */
    private $contentMetadataInspector;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var array<string|int, array{resource_key: string, entityClass?: string}>
     */
    private $routeMappings = [
        [
            'resource_key' => 'examples',
            'entityClass' => ExampleDimensionContent::class,
        ],
    ];

    /**
     * @var RouteRemover
     */
    private $routeRemover;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->routeRemover = new RouteRemover(
            $this->contentMetadataInspector->reveal(),
            $this->routeRepository->reveal(),
            $this->routeMappings
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame([
            Events::preRemove,
        ], $this->routeRemover->getSubscribedEvents());
    }

    public function testPreRemove(): void
    {
        $object = new Example();
        $property = new \ReflectionProperty(Example::class, 'id');
        $property->setAccessible(true);
        $property->setValue($object, '123-123-123');

        $this->contentMetadataInspector->getDimensionContentClass(Example::class)
            ->willReturn(ExampleDimensionContent::class);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $event = new LifecycleEventArgs($object, $entityManager->reveal());

        $route1 = $this->prophesize(RouteInterface::class);
        $route2 = $this->prophesize(RouteInterface::class);
        $this->routeRepository->findAllByEntity(Argument::any(), '123-123-123')
            ->willReturn([$route1->reveal(), $route2->reveal()]);

        $entityManager->remove($route1->reveal())->shouldBeCalled();
        $entityManager->remove($route2->reveal())->shouldBeCalled();

        $this->routeRemover->preRemove($event);
    }

    public function testPreRemoveNoMappingConfigured(): void
    {
        $object = new Example();
        $property = new \ReflectionProperty(Example::class, 'id');
        $property->setAccessible(true);
        $property->setValue($object, '123-123-123');

        $this->contentMetadataInspector->getDimensionContentClass(Example::class)
            ->willReturn(self::class); // For testing purpose we return the wrong dimension content class

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $event = new LifecycleEventArgs($object, $entityManager->reveal());

        $this->routeRepository->findAllByEntity(Argument::cetera())->shouldNotBeCalled();

        $this->routeRemover->preRemove($event);
    }

    public function testPreRemoveNoContentRichEntity(): void
    {
        $object = new \stdClass();

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $event = new LifecycleEventArgs($object, $entityManager->reveal());

        $this->routeRepository->findAllByEntity(Argument::cetera())->shouldNotBeCalled();

        $this->routeRemover->preRemove($event);
    }
}
