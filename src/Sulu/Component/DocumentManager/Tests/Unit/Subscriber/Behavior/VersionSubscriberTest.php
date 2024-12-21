<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Subscriber\Behavior\VersionSubscriber;
use Sulu\Component\DocumentManager\Version;
use Symfony\Bridge\PhpUnit\ClockMock;

class VersionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $session;

    /**
     * @var ObjectProphecy<Workspace>
     */
    private $workspace;

    /**
     * @var ObjectProphecy<VersionManagerInterface>
     */
    private $versionManager;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var \ReflectionProperty
     */
    private $checkoutPathsReflection;

    /**
     * @var \ReflectionProperty
     */
    private $checkpointPathsReflection;

    /**
     * @var VersionSubscriber
     */
    private $versionSubscriber;

    public function setUp(): void
    {
        $this->versionManager = $this->prophesize(VersionManagerInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->workspace = $this->prophesize(Workspace::class);
        $this->workspace->getVersionManager()->willReturn($this->versionManager->reveal());
        $this->session = $this->prophesize(SessionInterface::class);
        $this->session->getWorkspace()->willReturn($this->workspace->reveal());

        $this->versionSubscriber = new VersionSubscriber($this->session->reveal(), $this->propertyEncoder->reveal());

        $this->checkoutPathsReflection = new \ReflectionProperty(VersionSubscriber::class, 'checkoutUuids');
        $this->checkoutPathsReflection->setAccessible(true);

        $this->checkpointPathsReflection = new \ReflectionProperty(VersionSubscriber::class, 'checkpointUuids');
        $this->checkpointPathsReflection->setAccessible(true);
    }

    public function testSetVersionMixinOnPersist(): void
    {
        $event = $this->prophesize(PersistEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin('mix:versionable')->shouldBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPersistWithoutVersionBehavior(): void
    {
        $event = $this->prophesize(PersistEvent::class);

        $event->getDocument()->willReturn(new \stdClass());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin(Argument::any())->shouldNotBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPublish(): void
    {
        $event = $this->prophesize(PublishEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin('mix:versionable')->shouldBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionMixinOnPublishWithoutVersionBehavior(): void
    {
        $event = $this->prophesize(PublishEvent::class);

        $event->getDocument()->willReturn(new \stdClass());

        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin(Argument::any())->shouldNotBeCalled();
        $event->getNode()->willReturn($node->reveal());

        $this->versionSubscriber->setVersionMixin($event->reveal());
    }

    public function testSetVersionsOnDocument(): void
    {
        $event = $this->prophesize(HydrateEvent::class);

        $document = $this->prophesize(VersionBehavior::class);
        $event->getDocument()->willReturn($document->reveal())->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('sulu:versions', [])
            ->willReturn([
                '{"version":"1.0","locale":"de","author":null,"authored":"2016-12-06T09:37:21+01:00"}',
                '{"version":"1.1","locale":"en","author":1,"authored":"2016-12-05T19:47:22+01:00"}',
            ])->shouldBeCalled();
        $event->getNode()->willReturn($node->reveal())->shouldBeCalled();

        $document->setVersions(
            [
                new Version('1.0', 'de', null, new \DateTime('2016-12-06T09:37:21+01:00')),
                new Version('1.1', 'en', 1, new \DateTime('2016-12-05T19:47:22+01:00')),
            ]
        );

        $this->versionSubscriber->setVersionsOnDocument($event->reveal());
    }

    public function testSetVersionsOnDocumentWithoutVersionBehavior(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn(new \stdClass());
        $event->getNode()->shouldNotBeCalled();

        $this->versionSubscriber->setVersionsOnDocument($event->reveal());
    }

    public function testRememberCheckoutNodes(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(VersionBehavior::class);

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $node->getIdentifier()->willReturn('123-123-13');

        $this->versionSubscriber->rememberCheckoutUuids($event->reveal());

        $this->assertEquals(['123-123-13'], $this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }

    public function testRememberCheckoutNodesWithoutVersionBehavior(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = new \stdClass();

        $event->getDocument()->willReturn($document);

        $this->versionSubscriber->rememberCheckoutUuids($event->reveal());

        $this->assertEmpty($this->checkoutPathsReflection->getValue($this->versionSubscriber));
    }

    public function testRememberCreateVersionNodes(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(VersionBehavior::class)
            ->willImplement(LocaleBehavior::class);
        $document->getLocale()->willReturn('de');

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getOption('user')->willReturn(2);

        $node->getIdentifier()->willReturn('123-123-123');

        $this->versionSubscriber->rememberCreateVersion($event->reveal());

        $this->assertEquals(
            [['uuid' => '123-123-123', 'locale' => 'de', 'author' => 2]],
            $this->checkpointPathsReflection->getValue($this->versionSubscriber)
        );
    }

    public function testRememberCreateVersionNodesWithoutVersionBehavior(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $document = new \stdClass();

        $event->getDocument()->willReturn($document)->shouldBeCalled();

        $this->versionSubscriber->rememberCreateVersion($event->reveal());
    }

    public function testApplyVersionOperations(): void
    {
        ClockMock::register(VersionSubscriber::class);
        ClockMock::withClockMock(true);

        $this->checkoutPathsReflection->setValue($this->versionSubscriber, ['1-1-1-1', '2-2-2-2']);
        $this->checkpointPathsReflection->setValue($this->versionSubscriber, [
            ['uuid' => '3-3-3-3', 'locale' => 'de', 'author' => 1],
        ]);

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/node1');
        $this->session->getNodeByIdentifier('1-1-1-1')->willReturn($node->reveal())->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/node2');
        $this->session->getNodeByIdentifier('2-2-2-2')->willReturn($node->reveal())->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/node3');
        $this->session->getNodeByIdentifier('3-3-3-3')->willReturn($node->reveal())->shouldBeCalled();

        $this->versionManager->isCheckedOut('/node1')->willReturn(false)->shouldBeCalled();
        $this->versionManager->isCheckedOut('/node2')->willReturn(true)->shouldBeCalled();

        $this->versionManager->checkout('/node1')->shouldBeCalled();
        $this->versionManager->checkout('/node2')->shouldNotBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/node3')->willReturn($node->reveal())->shouldBeCalled();

        $version = $this->prophesize(VersionInterface::class);
        $version->getName()->willReturn('a')->shouldBeCalled();
        $this->versionManager->checkpoint('/node3')->willReturn($version->reveal());
        $node->getPropertyValueWithDefault('sulu:versions', [])->willReturn([
            '{"locale":"en","version":"0","author":null,"authored":"2016-12-05T19:47:22+01:00"}',
        ])->shouldBeCalled();
        $node->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0","author":null,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"de","version":"a","author":1,"authored":"' . \date('c', ClockMock::time()) . '"}',
            ]
        )->shouldBeCalled();
        $this->session->save()->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();

        $this->assertEquals([], $this->checkpointPathsReflection->getValue($this->versionSubscriber));
        $this->assertEquals([], $this->checkoutPathsReflection->getValue($this->versionSubscriber));

        ClockMock::withClockMock(false);
    }

    public function testApplyVersionOperationsWithMultipleCheckpoints(): void
    {
        $this->checkpointPathsReflection->setValue(
            $this->versionSubscriber,
            [
                ['uuid' => '1-1-1-1', 'locale' => 'de', 'author' => 2],
                ['uuid' => '1-1-1-1', 'locale' => 'en', 'author' => 3],
                ['uuid' => '2-2-2-2', 'locale' => 'en', 'author' => 1],
            ]
        );

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/node1');
        $this->session->getNodeByIdentifier('1-1-1-1')->willReturn($node->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/node2');
        $this->session->getNodeByIdentifier('2-2-2-2')->willReturn($node->reveal());

        $node1 = $this->prophesize(NodeInterface::class);
        $node1->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"fr","version":"0","author":1,"authored":"2016-12-05T19:47:22+01:00"}']);
        $this->session->getNode('/node1')->willReturn($node1->reveal());

        $node2 = $this->prophesize(NodeInterface::class);
        $node2->getPropertyValueWithDefault('sulu:versions', [])->willReturn(['{"locale":"en","version":"0","author":2,"authored":"2016-12-05T19:47:22+01:00"}']);
        $this->session->getNode('/node2')->willReturn($node2->reveal());

        $version1 = $this->prophesize(VersionInterface::class);
        $version2 = $this->prophesize(VersionInterface::class);
        $version3 = $this->prophesize(VersionInterface::class);
        $this->versionManager->checkpoint('/node1')->willReturn($version1->reveal());
        $this->versionManager->checkpoint('/node1')->willReturn($version2->reveal());
        $this->versionManager->checkpoint('/node2')->willReturn($version3->reveal());

        $version1->getName()->willReturn('a');
        $version2->getName()->willReturn('b');
        $version3->getName()->willReturn('c');

        $this->session->save()->shouldBeCalledTimes(1);
        $node1->setProperty(
            'sulu:versions',
            [
                '{"locale":"fr","version":"0","author":1,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"de","version":"b","author":2,"authored":"' . \date('c', ClockMock::time()) . '"}',
                '{"locale":"en","version":"b","author":3,"authored":"' . \date('c', ClockMock::time()) . '"}',
            ]
        )->shouldBeCalled();
        $node2->setProperty(
            'sulu:versions',
            [
                '{"locale":"en","version":"0","author":2,"authored":"2016-12-05T19:47:22+01:00"}',
                '{"locale":"en","version":"c","author":1,"authored":"' . \date('c', ClockMock::time()) . '"}',
            ]
        )->shouldBeCalled();

        $this->versionSubscriber->applyVersionOperations();
    }

    public function testRestoreLocalizedProperties(): void
    {
        $event = $this->prophesize(RestoreEvent::class);
        $document = $this->prophesize(VersionBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $versionHistory = $this->prophesize(VersionHistoryInterface::class);
        $version = $this->prophesize(VersionInterface::class);
        $frozenNode = $this->prophesize(NodeInterface::class);

        $node->getPath()->willReturn('/node');
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('i18n:de-test');
        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('non-translatable-test');
        $property3 = $this->prophesize(PropertyInterface::class);
        $property3->getName()->willReturn('jcr:uuid');
        $node->getProperties()->willReturn([$property1->reveal(), $property2->reveal(), $property3->reveal()]);
        $node->getNodes()->willReturn([]);

        $property1->remove()->shouldBeCalled();
        $property2->remove()->shouldBeCalled();
        $property3->remove()->shouldNotBeCalled();

        $this->propertyEncoder->localizedContentName('', 'de')->willReturn('i18n:de-');
        $this->propertyEncoder->localizedSystemName('', 'de')->willReturn('i18n:de-');

        $frozenNode->getNodes()->willReturn([]);
        $frozenNode->getPropertiesValues()->willReturn([
            'i18n:de-test' => 'Title',
            'non-translatable-test' => 'Article',
            'jcr:uuid' => 'asdf',
        ]);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getVersion()->willReturn('1.0');
        $event->getLocale()->willReturn('de');

        $this->versionManager->getVersionHistory('/node')->willReturn($versionHistory->reveal());
        $versionHistory->getVersion('1.0')->willReturn($version->reveal());
        $version->getFrozenNode()->willReturn($frozenNode->reveal());

        $node->setProperty('i18n:de-test', 'Title')->shouldBeCalled();
        $node->setProperty('non-translatable-test', 'Article')->shouldBeCalled();
        $node->setProperty('jcr:uuid', 'asdf')->shouldNotBeCalled();

        $this->versionSubscriber->restoreProperties($event->reveal());
    }
}
