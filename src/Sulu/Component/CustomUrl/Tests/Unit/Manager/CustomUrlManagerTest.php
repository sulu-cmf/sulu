<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlCreatedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRemovedEvent;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl as CustomUrlEntity;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Generator\Generator;
use Sulu\Component\CustomUrl\Manager\CustomUrlManager;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\CustomUrl\Repository\RowsIterator;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CustomUrlManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<CustomUrlRepositoryInterface>
     */
    private $customUrlRepository;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<DocumentDomainEventCollectorInterface>
     */
    private $documentDomainEventCollector;

    private CustomUrlManager $customUrlManager;

    protected function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentDomainEventCollector = $this->prophesize(DocumentDomainEventCollectorInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->customUrlManager = new CustomUrlManager(
            $this->customUrlRepository->reveal(),
            $this->webspaceManager->reveal(),
            'test',
            $this->documentDomainEventCollector->reveal(),
            new PropertyAccessor(),
            $this->entityManager->reveal(),
            new Generator(new Replacer()),
        );
    }

    public function testCreate(): void
    {
        $this->entityManager->persist(Argument::type(CustomUrlEntity::class))->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->documentDomainEventCollector->collect(Argument::type(CustomUrlCreatedEvent::class))->shouldBeCalled();

        $result = $this->customUrlManager->create(
            'sulu_io',
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['test-1'],
                'targetDocument' => '123-123-123',
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ]
        );

        $this->assertEquals('Test', $result->getTitle());
        $this->assertEquals('de', $result->getTargetLocale());
        $this->assertEquals('*.sulu.io', $result->getBaseDomain());
        $this->assertEquals(['test-1'], $result->getDomainParts());
        $this->assertEquals('123-123-123', $result->getTargetDocument());
        $this->assertTrue($result->isPublished());
        $this->assertTrue($result->isCanonical());
        $this->assertTrue($result->isRedirect());

        /** @var array<CustomUrlRoute> $routes */
        $routes = $result->getRoutes();
        $this->assertNotEmpty($routes);
        $this->assertEquals('test-1.sulu.io', $routes[0]->getPath());
    }

    public function testSave(): void
    {
        $customUrl = $this->prophesize(CustomUrlEntity::class);
        $customUrl->setTitle('Test')->shouldBeCalled();
        $customUrl->setPublished(true)->shouldBeCalled();
        $customUrl->setRedirect(true)->shouldBeCalled();
        $customUrl->setCanonical(true)->shouldBeCalled();
        $customUrl->setTargetDocument('123-123-123')->shouldBeCalled();
        $customUrl->setTargetLocale('de')->shouldBeCalled();
        $customUrl->setBaseDomain('*.sulu.io/*')->shouldBeCalled();
        $customUrl->setDomainParts(['test-1', 'some-part'])->shouldBeCalled();
        $customUrl
            ->addRoute(Argument::that(function(CustomUrlRoute $route) {
                return 'test-1.sulu.io/some-part' === $route->getPath();
            }))
            ->shouldBeCalled()
        ;

        $customUrl->getBaseDomain()->willReturn('*.sulu.io/*');
        $customUrl->getDomainParts()->willReturn(['test-1', 'some-part']);

        $this->customUrlManager->save(
            $customUrl->reveal(),
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io/*',
                'domainParts' => ['test-1', 'some-part'],
                'targetDocument' => '123-123-123',
                'canonical' => true,
                'redirect' => true,
                'targetLocale' => 'de',
            ]
        );
    }

    public function testDelete(): void
    {
        $this->customUrlRepository->findBy(['id' => ['123', '444']])
            ->shouldBeCalled()
            ->willReturn([new CustomUrlEntity(), new CustomUrlEntity()])
        ;

        $this->documentDomainEventCollector
            ->collect(Argument::type(CustomUrlRemovedEvent::class))
            ->shouldBeCalledTimes(2)
        ;

        $this->customUrlRepository->deleteByIds(['123', '444'])->shouldBeCalled();

        $this->customUrlManager->deleteByIds(['123', '444']);
    }

    //public function testDeleteRouteFromHistory(): void
    //{
    //$this->expectException(RouteNotRemovableException::class);

    //$customUrl = $this->prophesize(RouteDocument::class);
    //$customUrlDocument = $this->prophesize(CustomUrlDocument::class);

    //$customUrl->isHistory()->willReturn(false);
    //$customUrl->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test');
    //$customUrl->getTargetDocument()->willReturn($customUrlDocument->reveal());

    //$this->documentManager->find('123-123-123')->willReturn($customUrl->reveal());
    //$this->documentManager->remove($customUrl->reveal())->shouldNotBeCalled();

    //$this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
    //->willReturn('/cmf/sulu_io/custom_urls/routes');

    //$this->customUrlManager->deleteRoute('sulu_io', '123-123-123');
    //}
}
