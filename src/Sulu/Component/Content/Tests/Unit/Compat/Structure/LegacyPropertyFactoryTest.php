<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat\Structure;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyInterface as LegacyPropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Section\SectionPropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\NamespaceRegistry;

class LegacyPropertyFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NamespaceRegistry>
     */
    private $namespaceRegistry;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureFactory;

    /**
     * @var LegacyPropertyFactory
     */
    private $factory;

    /**
     * @var ObjectProphecy<PropertyMetadata>
     */
    private $property1;

    /**
     * @var ObjectProphecy<PropertyMetadata>
     */
    private $property2;

    /**
     * @var ObjectProphecy<SectionMetadata>
     */
    private $section;

    /**
     * @var ObjectProphecy<BlockMetadata>
     */
    private $block;

    /**
     * @var ObjectProphecy<ComponentMetadata>
     */
    private $component;

    /**
     * @var ObjectProphecy<ComponentMetadata>
     */
    private $component2;

    public function setUp(): void
    {
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->factory = new LegacyPropertyFactory(
            $this->namespaceRegistry->reveal(),
            $this->structureFactory->reveal()
        );

        $this->property1 = $this->prophesize(PropertyMetadata::class);
        $this->property2 = $this->prophesize(PropertyMetadata::class);
        $this->section = $this->prophesize(SectionMetadata::class);
        $this->block = $this->prophesize(BlockMetadata::class);
        $this->component = $this->prophesize(ComponentMetadata::class);
        $this->component2 = $this->prophesize(ComponentMetadata::class);
    }

    /**
     * It should create standard properties from "new" properties.
     *
     * @return ObjectProphecy<PropertyMetadata>
     */
    public function testCreateProperty()
    {
        $name = 'foo';
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];
        $placeholder = ['de' => 'Placehodler'];
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = [
            [
                'name' => 'prop',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
            [
                'name' => 'propfoo',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
        ];
        $colSpan = 6;

        $this->property1->getType()->willReturn($type);
        $this->property1->getName()->willReturn($name);
        $this->property1->isRequired()->willReturn($required);
        $this->property1->isLocalized()->willReturn($required);
        $this->property1->getMaxOccurs()->willReturn($maxOccurs);
        $this->property1->getMinOccurs()->willReturn($minOccurs);
        $this->property1->getColSpan()->willReturn($colSpan);
        $this->property1->getParameters()->willReturn($parameters);
        $this->property1->getTitles()->willReturn($title);
        $this->property1->getDescriptions()->willReturn($description);
        $this->property1->getPlaceholders()->willReturn($placeholder);
        $this->property1->getTags()->willReturn([]);
        $this->property1->getComponents()->willReturn([]);
        $this->property1->getDefaultComponentName()->willReturn(null);

        $legacyProperty = $this->factory->createProperty($this->property1->reveal());

        $this->assertInstanceOf(LegacyPropertyInterface::class, $legacyProperty);
        $this->assertEquals($legacyProperty->getContentTypeName(), $type);
        $this->assertEquals($legacyProperty->getName(), $name);
        $this->assertEquals($legacyProperty->getMandatory(), $required);
        $this->assertEquals($legacyProperty->getMultilingual(), $localized);
        $this->assertEquals($legacyProperty->getMaxOccurs(), $maxOccurs);
        $this->assertEquals($legacyProperty->getMinOccurs(), $minOccurs);
        $this->assertEquals($legacyProperty->getColSpan(), $colSpan);
        $this->assertContainsOnlyInstancesOf(PropertyParameter::class, $legacyProperty->getParams());
        $this->assertArrayHasKey('prop', $legacyProperty->getParams());
        $this->assertArrayHasKey('propfoo', $legacyProperty->getParams());
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertEquals($placeholder['de'], $legacyProperty->getPlaceholder('de'));

        return $this->property1;
    }

    /**
     * It should create a translated property.
     */
    #[\PHPUnit\Framework\Attributes\Depends('testCreateProperty')]
    public function testCreateTranslated(): void
    {
        $this->setUpProperty($this->property1);
        $this->namespaceRegistry->getPrefix('content_localized')->willReturn('i18n');
        $translatedProperty = $this->factory->createTranslatedProperty($this->property1->reveal(), 'de');
        $this->assertEquals('de', $translatedProperty->getLocalization());
        $this->assertEquals('i18n:de-name', $translatedProperty->getName());
    }

    /**
     * It should create a section property.
     *
     * @param ObjectProphecy<PropertyMetadata> $property
     */
    #[\PHPUnit\Framework\Attributes\Depends('testCreateProperty')]
    public function testCreateSection(ObjectProphecy $property): void
    {
        $name = 'foo';
        $parameters = ['foo', 'bar'];
        $colSpan = 6;
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];

        $this->section->getName()->willReturn($name);
        $this->section->getColSpan()->willReturn($colSpan);
        $this->section->getParameters()->willReturn($parameters);
        $this->section->getTitles()->willReturn($title);
        $this->section->getDescriptions()->willReturn($description);
        $this->section->getChildren()->willReturn([
            $property->reveal(),
        ]);

        $legacyProperty = $this->factory->createProperty($this->section->reveal());

        $this->assertInstanceOf(SectionPropertyInterface::class, $legacyProperty);
        $this->assertEquals($name, $legacyProperty->getName());
        $this->assertEquals($colSpan, $legacyProperty->getColSpan());
        $this->assertEquals($title['de'], $legacyProperty->getTitle('de'));
        $this->assertEquals($description['de'], $legacyProperty->getInfoText('de'));
        $this->assertCount(1, $legacyProperty->getChildProperties());
    }

    /**
     * It should create a block property.
     *
     * @param ObjectProphecy<PropertyMetadata> $property
     */
    #[\PHPUnit\Framework\Attributes\Depends('testCreateProperty')]
    public function testCreateBlock(ObjectProphecy $property): void
    {
        $this->setUpProperty($this->block);

        $this->component->getName()->willReturn('hai');
        $this->component->hasTag('sulu.global_block')->willReturn(false);
        $this->component->getChildren()->willReturn([
            $property->reveal(),
        ]);
        $this->component->getTitles()->willReturn([
            'de' => 'Testtitel',
            'en' => 'Test title',
        ]);
        $this->component->getDescriptions()->willReturn([
            'de' => 'Test Beschreibung',
            'en' => 'Test description',
        ]);

        $this->block->getComponents()->willReturn([
            $this->component->reveal(),
        ]);
        $this->block->getDefaultComponentName()->willReturn('foobar');

        /** @var BlockProperty $blockProperty */
        $blockProperty = $this->factory->createProperty($this->block->reveal());

        $this->assertInstanceOf(BlockPropertyInterface::class, $blockProperty);
        $this->assertCount(1, $blockProperty->getTypes());
        $blockType = $blockProperty->getType('hai');
        $this->assertNotNull($blockType);
        $this->assertCount(1, $blockType->getChildProperties());
        $this->assertEquals('Testtitel', $blockType->getMetadata()->get('title', 'de'));
        $this->assertEquals('Test title', $blockType->getMetadata()->get('title', 'en'));
    }

    /**
     * It should create a block property.
     *
     * @param ObjectProphecy<PropertyMetadata> $property
     */
    #[\PHPUnit\Framework\Attributes\Depends('testCreateProperty')]
    public function testCreateBlockWithGlobalBlock(ObjectProphecy $property): void
    {
        $this->setUpProperty($this->block);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getProperties()->willReturn([]);
        $this->structureFactory->getStructureMetadata('block', 'hai')->willReturn($structureMetadata->reveal());

        $this->component->getName()->willReturn('hai');
        $this->component->hasTag('sulu.global_block')->willReturn(true);
        $this->component->getChildren()->willReturn([
            $property->reveal(),
        ]);

        $this->block->getComponents()->willReturn([
            $this->component->reveal(),
        ]);
        $this->block->getDefaultComponentName()->willReturn('foobar');

        /** @var BlockProperty $blockProperty */
        $blockProperty = $this->factory->createProperty($this->block->reveal());

        $this->assertInstanceOf(BlockPropertyInterface::class, $blockProperty);
        $this->assertCount(1, $blockProperty->getTypes());
        $blockType = $blockProperty->getType('hai');
        $this->assertNotNull($blockType);
        $this->assertCount(0, $blockType->getChildProperties());
    }

    /**
     * It should create a block property.
     */
    #[\PHPUnit\Framework\Attributes\Depends('testCreateProperty')]
    public function testCreatePropertyWithTypes($child): void
    {
        $this->setUpProperty($this->property2);

        $this->component2->getName()->willReturn('hai');
        $this->component2->hasTag('sulu.global_block')->willReturn(false);
        $this->component2->getChildren()->willReturn([
            $child->reveal(),
        ]);
        $this->component2->getTitles()->willReturn([
            'de' => 'Testtitel',
            'en' => 'Test title',
        ]);
        $this->component2->getDescriptions()->willReturn([
            'de' => 'Test Beschreibung',
            'en' => 'Test description',
        ]);

        $this->property2->getComponents()->willReturn([
            $this->component2->reveal(),
        ]);
        $this->property2->getDefaultComponentName()->willReturn('foobar');

        /** @var Property $property */
        $property = $this->factory->createProperty($this->property2->reveal());

        $this->assertInstanceOf(PropertyInterface::class, $property);
        $this->assertCount(1, $property->getTypes());
        $type = $property->getType('hai');
        $this->assertNotNull($type);
        $this->assertCount(1, $type->getChildProperties());
        $this->assertEquals('Testtitel', $type->getMetadata()->get('title', 'de'));
        $this->assertEquals('Test title', $type->getMetadata()->get('title', 'en'));
    }

    /**
     * @param ObjectProphecy<PropertyMetadata> $property
     */
    private function setUpProperty($property)
    {
        $name = 'name';
        $title = ['de' => 'Tite'];
        $description = ['de' => 'Description'];
        $placeholder = ['de' => 'Placehodler'];
        $type = 'type';
        $required = true;
        $localized = true;
        $maxOccurs = 1;
        $minOccurs = 1;
        $parameters = [
            [
                'name' => 'prop',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
            [
                'name' => 'propfoo',
                'type' => 'type',
                'value' => 'value',
                'meta' => [],
            ],
        ];
        $colSpan = 6;

        $property->getType()->willReturn($type);
        $property->getName()->willReturn($name);
        $property->isRequired()->willReturn($required);
        $property->isLocalized()->willReturn($localized);
        $property->getMaxOccurs()->willReturn($maxOccurs);
        $property->getMinOccurs()->willReturn($minOccurs);
        $property->getColSpan()->willReturn($colSpan);
        $property->getParameters()->willReturn($parameters);
        $property->getTitles()->willReturn($title);
        $property->getDescriptions()->willReturn($description);
        $property->getPlaceholders()->willReturn($placeholder);
        $property->getTags()->willReturn([]);
        $property->getChildren()->willReturn([
            $this->component->reveal(),
        ]);
        $property->getDefaultComponentName()->willReturn(null);
        $property->getComponents()->willReturn([]);
    }
}
