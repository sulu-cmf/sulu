<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Snippet;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManager;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetNotFoundException;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Settings\SettingsManagerInterface;
use Sulu\Component\Webspace\Webspace;

class DefaultSnippetManagerTest extends TestCase
{
    use ProphecyTrait;

    private $defaultTypes = [
        'test' => [
            'key' => 'test',
            'template' => 'test',
            'title' => [
                'en' => 'Test EN',
                'de' => 'Test DE',
            ],
        ],
        'test_homepage' => [
            'key' => 'test_homepage',
            'template' => 'test',
            'title' => [
                'en' => 'Test Homepage EN',
                'de' => 'Test Homepage DE',
            ],
        ],
        'testCase' => [
            'key' => 'testCase',
            'template' => 'test',
            'title' => [
                'en' => 'Test Case EN',
                'de' => 'Test Case DE',
            ],
        ],
    ];

    public static function saveDataProvider()
    {
        return [
            ['sulu_io', 'de', 'test', 'test', '123-123-123'],
            ['sulu_io', 'de', 'test', 'test', '123-123-123', false],
            ['sulu_io', 'de', 'test', 'test', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', 'test', '123-123-123', false, false],
            ['sulu_io', 'de', 'test', 'test_homepage', '123-123-123'],
            ['sulu_io', 'de', 'test', 'test_homepage', '123-123-123', false],
            ['sulu_io', 'de', 'test', 'test_homepage', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', 'test_homepage', '123-123-123', false, false],
            ['sulu_io', 'de', 'test', 'testCase', '123-123-123'],
            ['sulu_io', 'de', 'test', 'testCase', '123-123-123', false],
            ['sulu_io', 'de', 'test', 'testCase', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', 'testCase', '123-123-123', false, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('saveDataProvider')]
    public function testSave(
        $webspaceKey,
        $locale,
        $structureType,
        $defaultType,
        $uuid,
        $exists = true,
        $sameType = true
    ): void {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $document = null;
        if ($exists) {
            if (!$sameType) {
                $this->expectException(WrongSnippetTypeException::class);
            }

            $document = $this->prophesize(SnippetDocument::class);
            $document->getStructureType()->willReturn($sameType ? $structureType : \strrev($structureType));

            $document = $document->reveal();

            $node = $this->prophesize(NodeInterface::class);
            $registry->getNodeForDocument($document)->willReturn($node->reveal());
        } else {
            $this->expectException(SnippetNotFoundException::class);
        }

        $documentManager->find($uuid, $locale, Argument::any())->shouldBeCalledTimes(1)->willReturn($document);

        $settingsManager->save($webspaceKey, 'snippets-' . $defaultType, Argument::type(NodeInterface::class))
            ->shouldBeCalledTimes($exists && $sameType ? 1 : 0);

        $domainEventCollector->collect(Argument::type(WebspaceDefaultSnippetModifiedEvent::class))
            ->shouldBeCalledTimes($exists && $sameType ? 1 : 0);
        $domainEventCollector->dispatch()
            ->shouldBeCalledTimes($exists && $sameType ? 1 : 0);

        $result = $manager->save($webspaceKey, $defaultType, $uuid, $locale);

        $this->assertEquals($result, $document);
    }

    public function testRemove(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $settingsManager->remove('sulu_io', 'snippets-test')->shouldBeCalledTimes(1);

        $domainEventCollector->collect(Argument::type(WebspaceDefaultSnippetRemovedEvent::class))->shouldBeCalled();
        $domainEventCollector->dispatch()->shouldBeCalled();

        $manager->remove('sulu_io', 'test');
    }

    public static function loadDataProvider()
    {
        return [
            ['sulu_io', 'de', 'test', '123-123-123'],
            ['sulu_io', 'de', 'test', '123-123-123', false],
            ['sulu_io', 'de', 'test', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', '123-123-123', false, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('loadDataProvider')]
    public function testLoad($webspaceKey, $locale, $type, $uuid, $exists = true, $sameType = true): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $document = null;
        $node = null;
        if ($exists) {
            if (!$sameType) {
                $this->expectException(WrongSnippetTypeException::class);
            }

            $document = $this->prophesize(SnippetDocument::class);
            $document->getStructureType()->willReturn($sameType ? $type : \strrev($type));

            $document = $document->reveal();
            $node = $this->prophesize(NodeInterface::class);
            $node->getIdentifier()->willReturn($uuid);
            $node = $node->reveal();

            $registry->getNodeForDocument($document)->willReturn($node);
        }

        $documentManager->find($uuid, $locale, Argument::any())
            ->shouldBeCalledTimes($exists ? 1 : 0)->willReturn($document);

        $settingsManager->load($webspaceKey, 'snippets-' . $type)
            ->shouldBeCalledTimes(1)->willReturn($exists ? $node : null);

        $result = $manager->load($webspaceKey, $type, $locale);

        $this->assertEquals($result, $document);
    }

    public function testLoadIdentifier(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $settingsManager->loadString('sulu_io', 'snippets-test')
            ->shouldBeCalledTimes(1)->willReturn('123-123-123');

        $uuid = $manager->loadIdentifier('sulu_io', 'test');

        $this->assertEquals('123-123-123', $uuid);
    }

    public function testIsDefault(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $webspace1 = new Webspace();
        $webspace1->setKey('test-1');
        $webspace2 = new Webspace();
        $webspace2->setKey('test-2');

        $webspaceManager->getWebspaceCollection()
            ->willReturn(new WebspaceCollection(['test-1' => $webspace1, 'test-2' => $webspace2]));
        $settingsManager->loadStringByWildcard('test-1', 'snippets-*')->willReturn(
            ['snippets-test-1' => '123', 'snippets-test-2' => '456']
        );
        $settingsManager->loadStringByWildcard('test-2', 'snippets-*')->willReturn(
            ['snippets-test-1' => '123-123-123', 'snippets-test-2' => '456']
        );

        $this->assertTrue($manager->isDefault('123-123-123'));
        $this->assertFalse($manager->isDefault('321-123-123'));
    }

    public function testLoadType(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $webspace1 = new Webspace();
        $webspace1->setKey('test-1');
        $webspace2 = new Webspace();
        $webspace2->setKey('test-2');

        $webspaceManager->getWebspaceCollection()
            ->willReturn(new WebspaceCollection(['test-1' => $webspace1, 'test-2' => $webspace2]));
        $settingsManager->loadStringByWildcard('test-1', 'snippets-*')->willReturn(
            ['snippets-test-1' => '123', 'snippets-test-2' => '456']
        );
        $settingsManager->loadStringByWildcard('test-2', 'snippets-*')->willReturn(
            ['snippets-test-1' => '123-123-123', 'snippets-test-2' => '456']
        );

        $this->assertEquals('test-1', $manager->loadType('123-123-123'));
        $this->assertEquals('test-2', $manager->loadType('456'));
        $this->assertEquals(null, $manager->loadType('321-321-321'));
    }

    public function testLoadWebspaces(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $webspace1 = new Webspace();
        $webspace1->setKey('test-1');
        $webspace2 = new Webspace();
        $webspace2->setKey('test-2');

        $webspaceManager->getWebspaceCollection()
            ->willReturn(new WebspaceCollection(['test-1' => $webspace1, 'test-2' => $webspace2]));
        $settingsManager->loadStringByWildcard('test-1', 'snippets-*')->willReturn(
            ['snippets-test-1' => '123-123-123']
        );
        $settingsManager->loadStringByWildcard('test-2', 'snippets-*')->willReturn(
            ['snippets-test-2' => '456']
        );

        $this->assertEquals([$webspace1], $manager->loadWebspaces('123-123-123'));
        $this->assertEquals([$webspace2], $manager->loadWebspaces('456'));
        $this->assertEquals([], $manager->loadWebspaces('321-321-321'));
    }

    public function testGetTypeForArea(): void
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(),
            $documentManager->reveal(),
            $webspaceManager->reveal(),
            $registry->reveal(),
            $domainEventCollector->reveal(),
            $this->defaultTypes
        );

        $this->assertEquals('test', $manager->getTypeForArea('test_homepage'));
        $this->assertEquals('test', $manager->getTypeForArea('testCase'));
        $this->assertEquals('test', $manager->getTypeForArea('test'));
    }
}
