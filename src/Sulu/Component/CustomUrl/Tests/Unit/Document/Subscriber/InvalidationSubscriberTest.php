<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Document\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\Subscriber\InvalidationSubscriber;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CustomUrlRepositoryInterface>
     */
    private ObjectProphecy $customUrlRepository;

    /**
     * @var ObjectProphecy<CacheManager>
     */
    private ObjectProphecy $cacheManager;

    private InvalidationSubscriber $subscriber;

    public function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn(Request::create('http://sulu.lo/'));

        $this->subscriber = new InvalidationSubscriber(
            $this->customUrlRepository->reveal(),
            $this->cacheManager->reveal(),
            $requestStack->reveal()
        );
    }

    public function testInvalidateDocumentBeforePublishing(): void
    {
        $customUrl = new CustomUrl();
        $route1 = new CustomUrlRoute($customUrl, 'sulu.lo/test-1');
        $route2 = new CustomUrlRoute($customUrl, 'sulu.lo/test-2');
        $customUrl->addRoute($route1);
        $customUrl->addRoute($route2);

        $document = $this->prophesize(BasePageDocument::class);

        $this->customUrlRepository
            ->findByTarget($document->reveal())
            ->shouldBeCalled()
            ->willReturn([$customUrl]);

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $event = new PublishEvent($document->reveal(), 'de');
        $this->subscriber->invalidateDocumentBeforePublishing($event);
    }

    public function testInvalidateCustomUrl(): void
    {
        $customUrl = new CustomUrl();
        $route1 = new CustomUrlRoute($customUrl, 'sulu.lo/test-1');
        $route2 = new CustomUrlRoute($customUrl, 'sulu.lo/test-2');
        $customUrl->addRoute($route1);
        $customUrl->addRoute($route2);

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $event = new PublishEvent($customUrl, 'de');
        $this->subscriber->invalidateDocumentBeforePublishing($event);
    }

    public function testInvalidateCustomUrlRoute(): void
    {
        $route = new CustomUrlRoute(new CustomUrl(), 'sulu.lo/test-1');

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();

        $event = new PublishEvent($route, 'de');
        $this->subscriber->invalidateDocumentBeforePublishing($event);
    }

    public function testInvalidateRouteDocument(): void
    {
        $routeDocument = $this->prophesize(RouteDocument::class);
        $routeDocument->getPath()->shouldBeCalled()->willReturn('sulu.lo/test-1');

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();

        $event = new RemoveEvent($routeDocument->reveal());
        $this->subscriber->invalidateDocumentBeforeRemoving($event);
    }
}
