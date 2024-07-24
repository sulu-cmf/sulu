<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class RoutableSubscriberTest extends TestCase
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainGenerator;

    /**
     * @var RouteManagerInterface
     */
    private $routeManager;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @var RoutableSubscriber
     */
    private $routableSubscriber;

    /**
     * @var RoutableBehavior
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->chainGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->metadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);

        $this->document = $this->prophesize(RoutableBehavior::class);

        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('123-123-123');

        $this->conflictResolver->resolve(Argument::type(RouteInterface::class))->will(
            function(array $arguments) {
                return $arguments[0];
            }
        );

        $this->routableSubscriber = new RoutableSubscriber(
            $this->chainGenerator->reveal(),
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->propertyEncoder->reveal(),
            $this->metadataFactory->reveal(),
            $this->conflictResolver->reveal()
        );
    }

    protected function prophesizeEvent($className)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($this->node->reveal());

        return $event->reveal();
    }

    public function testHandlePersist()
    {
        $document = $this->prophesize(RoutableBehavior::class);
        $document->willImplement(ChildrenBehavior::class);

        $event = $this->prophesize(AbstractMappingEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(Argument::any())->willReturn(false);

        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());
        $this->propertyEncoder->localizedSystemName('routePath', 'de')->willReturn('i18n:de-routePath');
        $this->propertyEncoder->localizedContentName('routePathName', 'de')->willReturn('i18n:de-routePathName');

        $children = [
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
        ];

        foreach ($children as $child) {
            $child->getStructureType()->willReturn('default');
            $this->documentInspector->getLocale($child->reveal())->willReturn('de');
        }

        $document->getChildren()
            ->willReturn([$children[0]->reveal(), $children[1]->reveal(), $children[2]->reveal()]);

        $nodes = [
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
        ];
        $this->documentInspector->getNode($children[0]->reveal())->willReturn($nodes[0]->reveal());
        $this->documentInspector->getNode($children[1]->reveal())->willReturn($nodes[1]->reveal());
        $this->documentInspector->getNode($children[2]->reveal())->willReturn($nodes[2]->reveal());

        $routes = [
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
        ];
        $routes[0]->getPath()->willReturn('/test-1');
        $routes[1]->getPath()->willReturn('/test-2');
        $routes[2]->getPath()->willReturn('/test-3');
        $this->chainGenerator->generate($children[0]->reveal())->willReturn($routes[0]->reveal());
        $this->chainGenerator->generate($children[1]->reveal())->willReturn($routes[1]->reveal());
        $this->chainGenerator->generate($children[2]->reveal())->willReturn($routes[2]->reveal());

        $children[0]->setRoutePath('/test-1')->shouldBeCalled();
        $children[1]->setRoutePath('/test-2')->shouldBeCalled();
        $children[2]->setRoutePath('/test-3')->shouldBeCalled();

        $nodes[0]->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();
        $nodes[0]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePath', '/test-3')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();

        $this->routableSubscriber->handlePersist($event->reveal());
    }

    public function testHandleRemove()
    {
        $this->document->willImplement(ChildrenBehavior::class);

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en']);

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $child2 = $this->prophesize(RoutableBehavior::class);
        $child2->getRoutePath()->willReturn('/test/test-2');

        $child1 = $this->prophesize(RoutableBehavior::class);
        $child1->willImplement(ChildrenBehavior::class);
        $child1->getChildren()->willReturn([$child2->reveal()]);
        $child1->getRoutePath()->willReturn('/test/test-1');

        $this->document->getChildren()->willReturn([$child1->reveal()]);

        $route1de = $this->prophesize(RouteInterface::class);
        $route1en = $this->prophesize(RouteInterface::class);
        $route2de = $this->prophesize(RouteInterface::class);
        $route2en = $this->prophesize(RouteInterface::class);

        $this->routeRepository->findByPath('/test/test-1', 'de')->willReturn($route1de->reveal());
        $this->routeRepository->findByPath('/test/test-1', 'en')->willReturn($route1en->reveal());
        $this->routeRepository->findByPath('/test/test-2', 'de')->willReturn($route2de->reveal());
        $this->routeRepository->findByPath('/test/test-2', 'en')->willReturn($route2en->reveal());

        $this->entityManager->remove($route1de->reveal())->shouldBeCalled();
        $this->entityManager->remove($route1en->reveal())->shouldBeCalled();
        $this->entityManager->remove($route2de->reveal())->shouldBeCalled();
        $this->entityManager->remove($route2en->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRemove($event->reveal());
    }

    public function testHandleCopy()
    {
        $this->document->willImplement(ChildrenBehavior::class);

        $event = $this->prophesize(CopyEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getCopiedPath()->willReturn('/cmf/articles/2017/04/test-article');

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->documentManager->find('/cmf/articles/2017/04/test-article', 'de')->willReturn($this->document->reveal());

        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');

        $this->chainGenerator->generate($this->document->reveal())->willReturn($route->reveal());

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');

        $children = [
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
        ];

        foreach ($children as $child) {
            $child->getStructureType()->willReturn('default');
            $this->documentInspector->getLocale($child->reveal())->willReturn('de');
        }

        $children[0]->getRoutePath()->willReturn('/test-old-1');
        $children[1]->getRoutePath()->willReturn('/test-old-2');
        $children[2]->getRoutePath()->willReturn('/test-old-3');

        $this->routeRepository->findByPath('/test-old-1', 'de')->willReturn(null);
        $this->routeRepository->findByPath('/test-old-2', 'de')->willReturn(null);
        $this->routeRepository->findByPath('/test-old-3', 'de')->willReturn(null);

        $children[0]->getRoute()->willReturn(null);
        $children[1]->getRoute()->willReturn(null);
        $children[2]->getRoute()->willReturn(null);

        $children[0]->getUuid()->willReturn('123-123-123');
        $children[1]->getUuid()->willReturn('231-231-231');
        $children[2]->getUuid()->willReturn('312-312-312');

        $children[0]->getClass()->willReturn('Article');
        $children[1]->getClass()->willReturn('Article');
        $children[2]->getClass()->willReturn('Article');

        $this->routeRepository->findByEntity('Article', '123-123-123', 'de')->willReturn(null);
        $this->routeRepository->findByEntity('Article', '231-231-231', 'de')->willReturn(null);
        $this->routeRepository->findByEntity('Article', '312-312-312', 'de')->willReturn(null);

        $this->document->getChildren()
            ->willReturn([$children[0]->reveal(), $children[1]->reveal(), $children[2]->reveal()]);

        $nodes = [
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
        ];
        $this->documentInspector->getNode($children[0]->reveal())->willReturn($nodes[0]->reveal());
        $this->documentInspector->getNode($children[1]->reveal())->willReturn($nodes[1]->reveal());
        $this->documentInspector->getNode($children[2]->reveal())->willReturn($nodes[2]->reveal());

        $routes = [
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
        ];

        $routes[0]->getPath()->willReturn('/test-1');
        $routes[1]->getPath()->willReturn('/test-2');
        $routes[2]->getPath()->willReturn('/test-3');

        $this->routeManager->create($children[0]->reveal())->willReturn($routes[0]->reveal());
        $this->routeManager->create($children[1]->reveal())->willReturn($routes[1]->reveal());
        $this->routeManager->create($children[2]->reveal())->willReturn($routes[2]->reveal());

        $this->entityManager->persist($routes[0])->shouldBeCalled();
        $this->entityManager->persist($routes[1])->shouldBeCalled();
        $this->entityManager->persist($routes[2])->shouldBeCalled();

        $children[0]->setRoutePath('/test-1')->shouldBeCalled();
        $children[1]->setRoutePath('/test-2')->shouldBeCalled();
        $children[2]->setRoutePath('/test-3')->shouldBeCalled();

        $nodes[0]->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePath', '/test-3')->shouldBeCalled();

        $this->routableSubscriber->handleCopy($event->reveal());
    }

    public function testHandleReorder()
    {
        $this->document->willImplement(ParentBehavior::class);

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $parentDocument->willImplement(StructureBehavior::class);
        $this->document->getParent()->willReturn($parentDocument->reveal());

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(Argument::any())->willReturn(false);

        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());
        $this->propertyEncoder->localizedSystemName('routePath', 'de')->willReturn('i18n:de-routePath');
        $this->propertyEncoder->localizedContentName('routePathName', 'de')->willReturn('i18n:de-routePathName');

        $children = [
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
        ];

        foreach ($children as $child) {
            $child->getStructureType()->willReturn('default');
            $this->documentInspector->getLocale($child)->willReturn('de');
        }

        $parentDocument->getChildren()
            ->willReturn([$children[0]->reveal(), $children[1]->reveal(), $children[2]->reveal()]);

        $nodes = [
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
        ];
        $this->documentInspector->getNode($children[0]->reveal())->willReturn($nodes[0]->reveal());
        $this->documentInspector->getNode($children[1]->reveal())->willReturn($nodes[1]->reveal());
        $this->documentInspector->getNode($children[2]->reveal())->willReturn($nodes[2]->reveal());

        $routes = [
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
        ];
        $routes[0]->getPath()->willReturn('/test-1');
        $routes[1]->getPath()->willReturn('/test-2');
        $routes[2]->getPath()->willReturn('/test-3');
        $this->chainGenerator->generate($children[0]->reveal())->willReturn($routes[0]->reveal());
        $this->chainGenerator->generate($children[1]->reveal())->willReturn($routes[1]->reveal());
        $this->chainGenerator->generate($children[2]->reveal())->willReturn($routes[2]->reveal());

        $children[0]->setRoutePath('/test-1')->shouldBeCalled();
        $children[1]->setRoutePath('/test-2')->shouldBeCalled();
        $children[2]->setRoutePath('/test-3')->shouldBeCalled();

        $nodes[0]->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();
        $nodes[0]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePath', '/test-3')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePathName', 'i18n:de-routePath')->shouldBeCalled();

        $this->routableSubscriber->handleReorder($event->reveal());
    }
}
