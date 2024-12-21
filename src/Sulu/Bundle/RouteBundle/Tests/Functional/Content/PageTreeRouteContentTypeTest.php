<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Content;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\RouteBundle\Content\Type\PageTreeRouteContentType;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;

class PageTreeRouteContentTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $documentRegistry;

    /**
     * @var ObjectProphecy<ChainRouteGeneratorInterface>
     */
    private $chainRouteGenerator;

    /**
     * @var ObjectProphecy<ConflictResolverInterface>
     */
    private $conflictResolver;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * @var PageTreeRouteContentType
     */
    private $contentType;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<BasePageDocument>
     */
    private $document;

    /**
     * @var string
     */
    private $propertyName = 'i18n:de-routePath';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var string
     */
    private $uuid = '123-123-123';

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->chainRouteGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->property->getName()->willReturn($this->propertyName);

        $this->route = new Route();
        $this->routeRepository->createNew()->willReturn($this->route);
        $this->conflictResolver->resolve($this->route)->willReturn($this->route);
        $this->entityManager->getRepository(Route::class)->willReturn($this->routeRepository->reveal());

        $this->contentType = new PageTreeRouteContentType(
            $this->documentManager->reveal(),
            $this->documentRegistry->reveal(),
            $this->chainRouteGenerator->reveal(),
            $this->conflictResolver->reveal(),
            $this->entityManager->reveal()
        );

        $this->document = $this->prophesize(BasePageDocument::class);
        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn($this->webspaceKey);
    }

    public function testRead(): void
    {
        $value = [
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
        ];

        $this->node->getPropertyValueWithDefault($this->propertyName, '')->willReturn($value['path']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-suffix', null)->willReturn($value['suffix']);
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);
        $this->node->getPropertyValue($this->propertyName . '-page', PropertyType::STRING)
            ->willReturn($value['page']['uuid']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-page-path', '')
            ->willReturn($value['page']['path']);

        $this->property->setValue($value)->shouldBeCalled();

        $result = $this->contentType->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            null
        );

        $this->assertEquals($value, $result);
    }

    public function testReadNotSet(): void
    {
        $value = [
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
            'page' => null,
        ];

        $this->node->getPropertyValueWithDefault($this->propertyName, '')->willReturn($value['path']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-suffix', null)->willReturn($value['suffix']);
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(false);

        $this->property->setValue($value)->shouldBeCalled();

        $result = $this->contentType->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            null
        );

        $this->assertEquals($value, $result);
    }

    public function testWrite(): void
    {
        $value = [
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, $value['path'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', $value['suffix'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'])
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $this->node->hasProperty($this->propertyName . '-page')->willReturn(false);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteString(): void
    {
        $value = '/test-page/test-custom-child';
        $this->property->getValue()->willReturn($value);

        $route = $this->prophesize(RouteInterface::class);
        $document = $this->prophesize(RoutableBehavior::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

        $this->node->setProperty($this->propertyName, '/')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', '/')->shouldBeCalled();

        $this->node->hasProperty($this->propertyName . '-page')->willReturn(false);
        $this->node->hasProperty($this->propertyName . '-page-path')->willReturn(false);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteExistingPageRelation(): void
    {
        $value = [
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, $value['path'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', $value['suffix'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteGeneratePath(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test-custom-child');

        $route->setPath('/test-page/test-custom-child')->shouldBeCalled()->will(
            function() use ($route): void {
                $route->getPath()->willReturn('/test-page/test-custom-child');
            }
        );

        $this->routeRepository->createNew()->willReturn($route);
        $this->conflictResolver->resolve($route)->shouldBeCalled()->willReturn($route);

        $document = $this->prophesize(RoutableBehavior::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

        $value = [
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, '/test-page/test-custom-child')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', '/test-custom-child')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'])
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteGeneratePathRoot(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test-custom-child');

        $route->setPath('/test-custom-child')->shouldBeCalled()->will(
            function() use ($route): void {
                $route->getPath()->willReturn('/test-custom-child');
            }
        );

        $this->routeRepository->createNew()->willReturn($route);
        $this->conflictResolver->resolve($route)->shouldBeCalled()->willReturn($route);

        $document = $this->prophesize(RoutableBehavior::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

        $value = [
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, '/test-custom-child')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', '/test-custom-child')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'])
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteNoParentPage(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/');
        $route->setPath('/')->shouldBeCalled();

        $this->routeRepository->createNew()->willReturn($route);
        $this->conflictResolver->resolve($route)->shouldBeCalled()->willReturn($route);

        $document = $this->prophesize(RoutableBehavior::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

        $value = [
            'page' => [
                'uuid' => null,
                'path' => null,
            ],
        ];

        $this->node->setProperty($this->propertyName, '/')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', '/')->shouldBeCalled();

        $pageProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $pageProperty->remove()->shouldBeCalled();
        $pagePathProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $pagePathProperty->remove()->shouldBeCalled();
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);
        $this->node->getProperty($this->propertyName . '-page')->willReturn($pageProperty->reveal());
        $this->node->hasProperty($this->propertyName . '-page-path')->willReturn(true);
        $this->node->getProperty($this->propertyName . '-page-path')->willReturn($pagePathProperty->reveal());

        $this->property->getValue()->willReturn($value);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testGetContentData(): void
    {
        $value = [
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
        ];

        $this->property->getValue()->willReturn($value);

        $this->assertEquals($value['path'], $this->contentType->getContentData($this->property->reveal()));
    }

    public function testGetViewData(): void
    {
        $value = [
            'page' => [
                'uuid' => $this->uuid,
                'path' => '/test-page',
            ],
            'path' => '/test-page/test-custom-child',
            'suffix' => '/test-custom-child',
        ];

        $this->property->getValue()->willReturn($value);

        $this->assertEquals($value, $this->contentType->getViewData($this->property->reveal()));
    }
}
