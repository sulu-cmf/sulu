<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlCreatedEvent;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;

class CustomUrlCreatedEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CustomUrl>
     */
    private $customUrl;

    public function setUp(): void
    {
        $this->customUrl = $this->prophesize(CustomUrl::class);
    }

    public function testGetCustomUrlDocument(): void
    {
        $event = $this->createCustomUrlCreatedEvent();

        static::assertSame($this->customUrl->reveal(), $event->getCustomUrl());
    }

    public function testGetEventType(): void
    {
        $event = $this->createCustomUrlCreatedEvent();

        static::assertSame('created', $event->getEventType());
    }

    public function testGetEventPayload(): void
    {
        $event = $this->createCustomUrlCreatedEvent('sulu-io', ['name' => 'test-name']);

        static::assertSame(['name' => 'test-name'], $event->getEventPayload());
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createCustomUrlCreatedEvent();

        static::assertSame('custom_urls', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createCustomUrlCreatedEvent();
        $this->customUrl->getUuid()->willReturn('1234-1234-1234-1234');

        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testGetResourceWebspaceKey(): void
    {
        $event = $this->createCustomUrlCreatedEvent('test-io');

        static::assertSame('test-io', $event->getResourceWebspaceKey());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createCustomUrlCreatedEvent();
        $this->customUrl->getTitle()->willReturn('custom-url-title');

        static::assertSame('custom-url-title', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createCustomUrlCreatedEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createCustomUrlCreatedEvent('test-io');

        static::assertSame('sulu.webspaces.test-io.custom-urls', $event->getResourceSecurityContext());
    }

    /**
     * @param mixed[] $payload
     */
    private function createCustomUrlCreatedEvent(
        string $webspaceKey = 'sulu-io',
        array $payload = []
    ): CustomUrlCreatedEvent {
        return new CustomUrlCreatedEvent(
            $this->customUrl->reveal(),
            $payload
        );
    }
}
