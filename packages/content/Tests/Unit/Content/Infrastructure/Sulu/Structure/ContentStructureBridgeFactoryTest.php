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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\Structure;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentStructureBridge;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentStructureBridgeFactory;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\StructureMetadataNotFoundException;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\MockWrapper;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\TemplateMockWrapperTrait;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

class ContentStructureBridgeFactoryTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getContentStructureBridgeFactory(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        LegacyPropertyFactory $propertyFactory
    ): ContentStructureBridgeFactory {
        return new ContentStructureBridgeFactory(
            $structureMetadataFactory, $propertyFactory
        );
    }

    /**
     * @param ObjectProphecy<TemplateInterface> $templateMock
     */
    protected function wrapTemplateMock(ObjectProphecy $templateMock): TemplateInterface
    {
        /** @var ObjectProphecy<object> $templateMock */
        return new class($templateMock) extends MockWrapper implements TemplateInterface {
            use TemplateMockWrapperTrait;
        };
    }

    public function testGetBridge(): void
    {
        $structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $contentStructureBridgeFactory = $this->getContentStructureBridgeFactory(
            $structureMetadataFactory->reveal(),
            $propertyFactory->reveal()
        );

        $object = $this->prophesize(TemplateInterface::class);
        $object->getTemplateKey()->willReturn('default');
        $object = $this->wrapTemplateMock($object);

        $metadata = $this->prophesize(StructureMetadata::class);
        $structureMetadataFactory->getStructureMetadata('mock-template-type', 'default')
            ->shouldBeCalled()->willReturn($metadata->reveal());

        $result = $contentStructureBridgeFactory->getBridge($object, 'content-id', 'de');

        $this->assertInstanceOf(ContentStructureBridge::class, $result);
        $this->assertSame($object, $result->getContent());
        $this->assertSame('content-id', $result->getUuid());
        $this->assertSame('de', $result->getLanguageCode());
    }

    public function testGetBridgeNoStructureMetadata(): void
    {
        $this->expectException(StructureMetadataNotFoundException::class);
        $this->expectExceptionMessage(\sprintf(
            'No structure metadata found for template type "%s" and template key "%s"',
            'mock-template-type',
            'default'
        ));

        $structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $contentStructureBridgeFactory = $this->getContentStructureBridgeFactory(
            $structureMetadataFactory->reveal(),
            $propertyFactory->reveal()
        );

        $object = $this->prophesize(TemplateInterface::class);
        $object->getTemplateKey()->willReturn('default');
        $object = $this->wrapTemplateMock($object);

        $structureMetadataFactory->getStructureMetadata('mock-template-type', 'default')
            ->shouldBeCalled()->willReturn(null);

        $contentStructureBridgeFactory->getBridge($object, 'content-id', 'de');
    }
}
