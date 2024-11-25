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
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentDocument;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentStructureBridge;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class ContentStructureBridgeTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createStructureBridge(
        ?TemplateInterface $content = null,
        ?StructureMetadata $structure = null,
        ?LegacyPropertyFactory $propertyFactory = null,
        string $id = '123-123-123',
        string $locale = 'en'
    ): ContentStructureBridge {
        return new ContentStructureBridge(
            $structure ?: $this->prophesize(StructureMetadata::class)->reveal(),
            $propertyFactory ?: $this->prophesize(LegacyPropertyFactory::class)->reveal(),
            $content ?: $this->prophesize(TemplateInterface::class)->reveal(),
            $id,
            $locale
        );
    }

    public function testGetDocument(): void
    {
        $content = $this->prophesize(TemplateInterface::class);

        $structure = $this->createStructureBridge($content->reveal());

        $result = $structure->getDocument();
        $this->assertInstanceOf(ContentDocument::class, $result);
        $this->assertSame($content->reveal(), $result->getContent());
        $this->assertSame('en', $result->getLocale());
    }

    public function testGetStructure(): void
    {
        $structureMetadata = $this->prophesize(StructureMetadata::class);

        $structureBridge = $this->createStructureBridge(null, $structureMetadata->reveal());

        $this->assertSame($structureMetadata->reveal(), $structureBridge->getStructure());
    }

    public function testGetContent(): void
    {
        $content = $this->prophesize(TemplateInterface::class);

        $structure = $this->createStructureBridge($content->reveal());

        $this->assertSame($content->reveal(), $structure->getContent());
    }

    public function testGet(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->createStructureBridge($content->reveal());

        $this->assertSame($content->reveal(), $structure->getContent());
    }

    public function testSetLanguageCode(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setLanguageCode('de');
    }

    public function testGetLanguageCode(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame('en', $structure->getLanguageCode());
    }

    public function testSetWebspaceKey(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setWebspaceKey('sulu_io');
    }

    public function testGetWebspaceKey(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getWebspaceKey());
    }

    public function testSetUuid(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setUuid('456-456-456');
    }

    public function testGetUuid(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame('123-123-123', $structure->getUuid());
    }

    public function testGetView(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->getView()->willReturn('pages/default');

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertSame('pages/default', $structure->getView());
    }

    public function testGetController(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->getController()->willReturn('Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction');

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertSame(
            'Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction',
            $structure->getController()
        );
    }

    public function testGetCacheLifeTime(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->getCacheLifeTime()->willReturn(['type' => 'type', 'value' => 'value']);

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertSame(
            ['type' => 'type', 'value' => 'value'],
            $structure->getCacheLifeTime()
        );
    }

    public function testGetKey(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->getName()->willReturn('default');

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertSame('default', $structure->getKey());
    }

    public function testSetCreator(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setCreator(1);
    }

    public function testGetCreator(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getCreator());
    }

    public function testSetChanger(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setChanger(1);
    }

    public function testGetChanger(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getChanger());
    }

    public function testSetCreated(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setCreated(new \DateTime());
    }

    public function testGetCreated(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getCreated());
    }

    public function testSetChanged(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setChanged(new \DateTime());
    }

    public function testGetChanged(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getChanged());
    }

    public function testHasProperty(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->hasProperty('title')->willReturn(true);

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertTrue($structure->hasProperty('title'));
    }

    public function testHasTag(): void
    {
        $structure = $this->prophesize(StructureMetadata::class);

        $structure->hasPropertyWithTagName('sulu.title')->willReturn(true);

        $structure = $this->createStructureBridge(null, $structure->reveal());

        $this->assertTrue($structure->hasTag('sulu.title'));
    }

    public function testGetProperty(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->hasProperty('title')->willReturn(true);
        $structure->getProperty('title')->willReturn($property->reveal());

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame($legacyProperty->reveal(), $structure->getProperty('title'));
    }

    public function testGetPropertyChild(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->hasProperty('title')->willReturn(false);
        $structure->getChild('title')->willReturn($property->reveal());

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame($legacyProperty->reveal(), $structure->getProperty('title'));
    }

    public function testGetPropertyByTagName(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->hasProperty('title')->willReturn(true);
        $structure->getPropertyByTagName('sulu.title', true)->willReturn($property->reveal());

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame($legacyProperty->reveal(), $structure->getPropertyByTagName('sulu.title', true));
    }

    public function testGetPropertiesByTagName(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->hasProperty('title')->willReturn(true);
        $structure->getPropertiesByTagName('sulu.title')->willReturn([$property->reveal()]);

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame([$legacyProperty->reveal()], $structure->getPropertiesByTagName('sulu.title'));
    }

    public function testGetPropertyValue(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->hasProperty('title')->willReturn(true);
        $structure->getProperty('title')->willReturn($property->reveal());

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $legacyProperty->getValue()->willReturn('Test');
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame('Test', $structure->getPropertyValue('title'));
        $this->assertSame('Test', $structure->__get('title'));
    }

    public function testGetPropertyValueByTagName(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->getPropertyByTagName('sulu.title', true)->willReturn($property->reveal());

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $legacyProperty->getValue()->willReturn('Test');
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame('Test', $structure->getPropertyValueByTagName('sulu.title'));
    }

    public function testGetProperties(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->getChildren()->willReturn([$property->reveal()]);

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame(['title' => $legacyProperty->reveal()], $structure->getProperties());
    }

    public function testGetPropertyNames(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->getChildren()->willReturn(['title' => $property->reveal()]);

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame(['title'], $structure->getPropertyNames());
    }

    public function testGetPropertiesFlated(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $structure = $this->prophesize(StructureMetadata::class);
        $factory = $this->prophesize(LegacyPropertyFactory::class);

        $property = $this->prophesize(PropertyMetadata::class);

        $structure->getProperties()->willReturn([$property->reveal()]);

        $property->getName()->willReturn('title');

        $legacyProperty = $this->prophesize(Property::class);
        $factory->createProperty($property->reveal(), Argument::any())->willReturn($legacyProperty->reveal());

        $legacyProperty->setPropertyValue(
            Argument::that(
                function(PropertyValue $propertyValue) {
                    return 'title' === $propertyValue->getName() && 'Test' === $propertyValue->getValue();
                }
            )
        )->shouldBeCalled();

        $content->getTemplateData()->willReturn(['title' => 'Test']);

        $structure = $this->createStructureBridge($content->reveal(), $structure->reveal(), $factory->reveal());

        $this->assertSame(['title' => $legacyProperty->reveal()], $structure->getProperties(true));
    }

    public function testSetHasChildren(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setHasChildren(false);
    }

    public function testSetChildren(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setChildren([]);
    }

    public function testGetHasChildren(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertFalse($structure->getHasChildren());
    }

    public function testGetChildren(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertEmpty($structure->getChildren());
    }

    public function testGetPublishedState(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertTrue($structure->getPublishedState());
    }

    public function testSetPublished(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setPublished(new \DateTime());
    }

    public function testGetPublished(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getPublished());
    }

    public function testSetType(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setType($this->prophesize(StructureType::class)->reveal());
    }

    public function testGetType(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getType());
    }

    public function testSetPath(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setPath('/test');
    }

    public function testGetPath(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getPath());
    }

    public function testSetHasTranslation(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->setHasTranslation(true);
    }

    public function testGetHasTranslation(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertTrue($structure->getHasTranslation());
    }

    public function testToArray(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertEmpty($structure->toArray());
    }

    public function testJsonSerialize(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertEmpty($structure->jsonSerialize());
    }

    public function testGetNodeType(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame(RedirectType::NONE, $structure->getNodeType());
    }

    public function testGetNodeName(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getNodeName());
    }

    public function testGetLocalizedTitle(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getLocalizedTitle('de'));
    }

    public function testGetNodeState(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame(WorkflowStage::PUBLISHED, $structure->getNodeState());
    }

    public function testCopyFrom(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $structure = $this->createStructureBridge();

        $structure->copyFrom($this->prophesize(StructureInterface::class)->reveal());
    }

    public function testGetInternal(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertFalse($structure->getInternal());
    }

    public function testGetNavContexts(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame([], $structure->getNavContexts());
    }

    public function testGetShadowLocales(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame([], $structure->getShadowLocales());
    }

    public function testGetShadowBaseLanguage(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getShadowBaseLanguage());
    }

    public function testGetIsShadow(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertFalse($structure->getIsShadow());
    }

    public function testGetShadowLocalesWithShadowEnabled(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(ShadowInterface::class);
        $content->getShadowLocales()->shouldBeCalled()->willReturn(['de' => 'en', 'en' => 'en']);

        $structure = $this->createStructureBridge($content->reveal());

        $this->assertSame(['de' => 'en', 'en' => 'en'], $structure->getShadowLocales());
    }

    public function testGetShadowBaseLanguageWithShadowEnabled(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(ShadowInterface::class);
        $content->getShadowLocale()->shouldBeCalled()->willReturn('de');

        $structure = $this->createStructureBridge($content->reveal());

        $this->assertSame('de', $structure->getShadowBaseLanguage());
    }

    public function testGetIsShadowWithShadowEnabled(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(ShadowInterface::class);
        $content->getShadowLocale()->shouldBeCalled()->willReturn('de');

        $structure = $this->createStructureBridge($content->reveal());

        $this->assertTrue($structure->getIsShadow());
    }

    public function testGetContentLocales(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertSame([], $structure->getContentLocales());
    }

    public function testGetContentLocalesWithDimensionContentInterface(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(DimensionContentInterface::class);
        $content->getAvailableLocales()->shouldBeCalled()->willReturn(['de', 'en']);
        $structure = $this->createStructureBridge($content->reveal());

        $this->assertSame(['de', 'en'], $structure->getContentLocales());
    }

    public function testGetOriginalTemplate(): void
    {
        $structure = $this->createStructureBridge();

        $this->assertNull($structure->getOriginTemplate());
    }
}
