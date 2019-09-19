<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\EventListener\WebspaceSerializeEventSubscriber;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceSerializeEventSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $webspaceUrlProvider = $this->prophesize(WebspaceUrlProviderInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), $webspaceUrlProvider->reveal(), 'prod');

        $events = $subscriber->getSubscribedEvents();

        $reflection = new \ReflectionClass(get_class($subscriber));

        foreach ($events as $event) {
            $this->assertTrue($reflection->hasMethod($event['method']));
            $this->assertEquals('json', $event['format']);
            $this->assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
        }
    }

    public function testAppendPortalInformation()
    {
        $webspaceUrlProvider = $this->prophesize(WebspaceUrlProviderInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), $webspaceUrlProvider->reveal(), 'prod');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $portalInformation = [
            'test-1' => new PortalInformation(1),
            'test-2' => new PortalInformation(2),
        ];

        $context = $this->prophesize(Context::class);
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $graphNavigator->accept(array_values($portalInformation))->willReturn('[{}, {}]');
        $visitor->visitProperty(
            new StaticPropertyMetadata(null, 'portalInformation', '[{}, {}]'),
            null
        );

        $webspaceManager->getPortalInformationsByWebspaceKey('prod', 'sulu_io')->willReturn($portalInformation);

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendPortalInformation');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }

    public function testAppendUrls()
    {
        $urls = [
            new Url('sulu.lo'),
            new Url('*.sulu.lo'),
            new Url('sulu.io'),
            new Url('*.sulu.io'),
        ];

        $webspaceUrlProvider = $this->prophesize(WebspaceUrlProviderInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), $webspaceUrlProvider->reveal(), 'prod');

        $webspace = $this->prophesize(Webspace::class);
        $webspaceUrlProvider->getUrls($webspace->reveal(), 'prod')->willReturn($urls);

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $serialzedData = '[{"url": "sulu.lo"}, {"url": "*.sulu.lo"}, {"url": "sulu.io"}, {"url": "*.sulu.io"}]';
        $context->getNavigator()->accept($urls)->willReturn($serialzedData);
        $visitor->addData('urls', $serialzedData)->shouldBeCalled();

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendUrls');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }

    public function testAppendCustomUrls()
    {
        $customUrls = [
            new CustomUrl('sulu.lo'),
            new CustomUrl('*.sulu.lo'),
            new CustomUrl('sulu.io'),
            new CustomUrl('*.sulu.io'),
        ];

        $locales = [new Localization('de'), new Localization('en')];

        $environments = [$this->prophesize(Environment::class), $this->prophesize(Environment::class)];
        $portals = [$this->prophesize(Portal::class), $this->prophesize(Portal::class)];
        $portals[0]->getEnvironment('prod')->willReturn($environments[0]->reveal());
        $portals[0]->getLocalizations()->willReturn($locales);
        $portals[1]->getEnvironment('prod')->willReturn($environments[1]->reveal());
        $portals[1]->getLocalizations()->willReturn($locales);

        $environments[0]->getCustomUrls()->willReturn([$customUrls[0], $customUrls[1]]);
        $environments[1]->getCustomUrls()->willReturn([$customUrls[2], $customUrls[3]]);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn(
            array_map(
                function($portal) {
                    return $portal->reveal();
                },
                $portals
            )
        );

        $webspaceUrlProvider = $this->prophesize(WebspaceUrlProviderInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), $webspaceUrlProvider->reveal(), 'prod');

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $serialzedData = '[{"url": "sulu.lo", "locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "*.sulu.lo","locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "sulu.io","locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "*.sulu.io","locales": [{"localization":"de"}, {"localization":"en"}]}]';
        $context->getNavigator()->accept($customUrls[0])->willReturn(['url' => 'sulu.lo']);
        $context->getNavigator()->accept($customUrls[1])->willReturn(['url' => '*.sulu.lo']);
        $context->getNavigator()->accept($customUrls[2])->willReturn(['url' => 'sulu.io']);
        $context->getNavigator()->accept($customUrls[3])->willReturn(['url' => '*.sulu.io']);
        $context->getNavigator()->accept($locales)->willReturn([['localization' => 'de'], ['localization' => 'en']]);
        $context->getNavigator()->accept(
            [
                ['url' => 'sulu.lo', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => '*.sulu.lo', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => 'sulu.io', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => '*.sulu.io', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
            ]
        )->willReturn($serialzedData);
        $visitor->visitProperty(
            new StaticPropertyMetadata(null, 'customUrls', $serialzedData), null
        )->shouldBeCalled();

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendCustomUrls');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }
}
