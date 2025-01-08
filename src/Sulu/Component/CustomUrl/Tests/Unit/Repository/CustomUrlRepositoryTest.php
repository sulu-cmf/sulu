<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Repository;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class CustomUrlRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContentRepositoryInterface>
     */
    private ObjectProphecy $contentRepository;

    /**
     * @var ObjectProphecy<GeneratorInterface>
     */
    private ObjectProphecy $customUrlGenerator;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private ObjectProphecy $webspaceManager;

    private CustomUrlRepository $customUrlRepository;

    public function setUp(): void
    {
        $this->contentRepository = $this->prophesize(ContentRepositoryInterface::class);
        $this->customUrlGenerator = $this->prophesize(GeneratorInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->customUrlRepository = new CustomUrlRepository(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $this->contentRepository->reveal(),
            $this->customUrlGenerator->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    //public function testFindByWebspace(): void
    //{
        //$environment = new Environment();
        //$environment->setType('test');
        //$environment->addCustomUrl(new CustomUrl('*.sulu.io'));
        //$environment->addCustomUrl(new CustomUrl('sulu.io/*'));

        //$portal = new Portal();
        //$portal->addEnvironment($environment);

        //$webspace = new Webspace();
        //$webspace->addPortal($portal);

        //$this->webspaceManager->findWebspaceByKey('sulu_io')->shouldBeCalled()->willReturn($webspace);

        //$result = $this->customUrlRepository->findByWebspaceKey('sulu_io');

        //$this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    //}

    //public function testFindUrls(): void
    //{
    //$this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_items%'])
    //->willReturn('/cmf/sulu_io/custom_urls/items');

    //$this->customUrlRepository->findUrls('/cmf/sulu_io/custom_urls/items')
    //->willReturn(['1.sulu.lo', '1.sulu.lo/2']);

    //$result = $this->customUrlManager->findUrls('sulu_io');

    //$this->assertEquals(['1.sulu.lo', '1.sulu.lo/2'], $result);
    //}

    //public function testFindHistoryRoutesById(): void
    //{
    //$customUrlDocument = $this->prophesize(CustomUrlDocument::class);
    //$this->documentManager->find('123-456-789', 'en', ['load_ghost_content' => true])
    //->willReturn($customUrlDocument->reveal());

    //$this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
    //->willReturn('/cmf/sulu_io/custom_urls/routes');

    //$routeDocument1 = $this->prophesize(RouteDocument::class);
    //$routeDocument1->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test1');
    //$routeDocument1->isHistory()->willReturn(true);
    //$routeDocument2 = $this->prophesize(RouteDocument::class);
    //$routeDocument2->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test2');
    //$routeDocument2->isHistory()->willReturn(false);
    //$routeDocument3 = $this->prophesize(RouteDocument::class);
    //$routeDocument3->getPath()->willReturn('/cmf/sulu_io/custom_urls/routes/sulu.io/test3');
    //$routeDocument3->isHistory()->willReturn(true);

    //$this->documentInspector->getReferrers($customUrlDocument->reveal())->willReturn(
    //[
    //$routeDocument1->reveal(),
    //$routeDocument2->reveal(),
    //]
    //);

    //$this->documentInspector->getReferrers($routeDocument1)->willReturn([$routeDocument3]);
    //$this->documentInspector->getReferrers($routeDocument2)->willReturn([]);
    //$this->documentInspector->getReferrers($routeDocument3)->willReturn([]);

    //$this->assertEquals(
    //['sulu.io/test1' => $routeDocument1->reveal(), 'sulu.io/test3' => $routeDocument3->reveal()],
    //$this->customUrlManager->findHistoryRoutesById('123-456-789', 'sulu_io')
    //);
    //}

    //public function testFindByUrl(): void
    //{
    //$routeDocument = $this->prophesize(RouteDocument::class);
    //$customUrlDocument = $this->prophesize(CustomUrlDocument::class);

    //$routeDocument->getTargetDocument()->willReturn($customUrlDocument->reveal());

    //$this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
    //->willReturn('/cmf/sulu_io/custom_urls/routes');

    //$this->documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'de', ['load_ghost_content' => true])
    //->willReturn($routeDocument->reveal());

    //$result = $this->customUrlManager->findByUrl('sulu.io/test', 'sulu_io', 'de');

    //$this->assertEquals($customUrlDocument->reveal(), $result);
    //}

    //public function testFindRouteByUrl(): void
    //{
    //$routeDocument = $this->prophesize(RouteDocument::class);

    //$this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
    //->willReturn('/cmf/sulu_io/custom_urls/routes');

    //$this->documentManager->find('/cmf/sulu_io/custom_urls/routes/sulu.io/test', 'en', ['load_ghost_content' => true])
    //->willReturn($routeDocument->reveal());

    //$result = $this->customUrlManager->findRouteByUrl('sulu.io/test', 'sulu_io', 'en');

    //$this->assertEquals($routeDocument->reveal(), $result);
    //}
}
