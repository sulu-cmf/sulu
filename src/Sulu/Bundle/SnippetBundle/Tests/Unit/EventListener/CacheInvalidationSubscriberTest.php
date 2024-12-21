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

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\EventListener\CacheInvalidationSubscriber;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class CacheInvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<DefaultSnippetManagerInterface>|DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var ObjectProphecy<CacheManager>|CacheManager
     */
    private $cacheManager;

    /**
     * @var array<int, array{
     *     key: string,
     *     cache-invalidation: string
     * }>
     */
    private array $areas;

    private CacheInvalidationSubscriber $cacheInvalidationSubscriber;

    protected function setUp(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->areas = [
            ['key' => 'area1', 'cache-invalidation' => 'true'],
            ['key' => 'area2', 'cache-invalidation' => 'false'],
        ];
        $this->cacheInvalidationSubscriber = new CacheInvalidationSubscriber(
            $this->defaultSnippetManager->reveal(),
            $this->cacheManager->reveal(),
            $this->areas
        );
    }

    public function testInvalidateSnippetAreaOnModified(): void
    {
        $snippet = new SnippetDocument();
        self::setPrivateProperty($snippet, 'uuid', '1234');

        $event = new SnippetModifiedEvent($snippet, 'de', []);
        $this->defaultSnippetManager->loadType('1234')->willReturn('area1');

        $this->cacheManager->invalidateReference('snippet_area', 'area1')
            ->shouldBeCalled();

        $this->cacheInvalidationSubscriber->invalidateSnippetAreaOnModified($event);
    }

    public function testInvalidateSnippetAreaOnRemoved(): void
    {
        $event = new SnippetRemovedEvent('1234', null, null);
        $this->defaultSnippetManager->loadType('1234')->willReturn('area1');

        $this->cacheManager->invalidateReference('snippet_area', 'area1')
            ->shouldBeCalled();

        $this->cacheInvalidationSubscriber->invalidateSnippetAreaOnRemoved($event);
    }

    public function testInvalidateSnippetAreaOnAreaRemoved(): void
    {
        $event = new WebspaceDefaultSnippetRemovedEvent('1234', 'area1');

        $this->cacheManager->invalidateReference('snippet_area', 'area1')
            ->shouldBeCalled();

        $this->cacheInvalidationSubscriber->invalidateSnippetAreaOnAreaRemoved($event);
    }

    public function testInvalidateSnippetAreaOnAreaModified(): void
    {
        $snippet = new SnippetDocument();
        self::setPrivateProperty($snippet, 'uuid', '1234');

        $event = new WebspaceDefaultSnippetModifiedEvent('1234', 'area1', $snippet);

        $this->cacheManager->invalidateReference('snippet_area', 'area1')
            ->shouldBeCalled();

        $this->cacheInvalidationSubscriber->invalidateSnippetAreaOnAreaModified($event);
    }

    public function testInvalidateSnippetAreaWhenCacheManagerIsNull(): void
    {
        $cacheInvalidationSubscriber = new CacheInvalidationSubscriber(
            $this->defaultSnippetManager->reveal(),
            null,
            $this->areas
        );

        $snippet = new SnippetDocument();
        self::setPrivateProperty($snippet, 'uuid', '1234');
        $event = new SnippetModifiedEvent($snippet, 'de', []);
        $this->defaultSnippetManager->loadType('1234')
            ->shouldNotBeCalled();

        $cacheInvalidationSubscriber->invalidateSnippetAreaOnModified($event);
    }

    public function testInvalidateSnippetAreaWhenCacheInvalidationIsFalse(): void
    {
        $snippet = new SnippetDocument();
        self::setPrivateProperty($snippet, 'uuid', '1234');
        $event = new SnippetModifiedEvent($snippet, 'de', []);
        $this->defaultSnippetManager->loadType('1234')
            ->willReturn('area2');
        $this->cacheManager->invalidateReference('snippet_area', 'area2')
            ->shouldNotBeCalled();

        $this->cacheInvalidationSubscriber->invalidateSnippetAreaOnModified($event);
    }
}
