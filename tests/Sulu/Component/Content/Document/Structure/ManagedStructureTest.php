<?php

namespace Sulu\Component\Content\Document\Property;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;

class ManagedStructureTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->structure = $this->prophesize(StructureMetadata::class);
        $this->document = $this->prophesize(StructureBehavior::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->structureProperty = $this->prophesize(PropertyMetadata::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->legacyProperty = $this->prophesize(PropertyInterface::class);

        $this->propertyContainer = new ManagedStructure(
            $this->contentTypeManager->reveal(),
            $this->propertyFactory->reveal(),
            $this->inspector->reveal(),
            $this->document->reveal()
        );

        $this->inspector->getNode($this->document->reveal())->willReturn($this->node->reveal());
        $this->inspector->getStructure($this->document->reveal())->willReturn($this->structure->reveal());
    }

    /**
     * It shuld lazily initialize a localized property
     */
    public function testGetLocalizedProperty()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $locale = 'fr';

        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->structureProperty->isLocalized()->willReturn(true);
        $this->doGetProperty($name, $contentTypeName, $locale);
    }

    /**
     * It should lazily initialize a non-localized property
     */
    public function testGetNonLocalizedProperty()
    {
        $name = 'test';
        $contentTypeName = 'hello';

        $this->document->getLocale()->shouldNotBeCalled();
        $this->structureProperty->isLocalized()->willReturn(false);

        $this->doGetProperty($name, $contentTypeName, null);
    }

    /**
     * It should act as an array
     */
    public function testArrayAccess()
    {
        $name = 'test';
        $contentTypeName = 'hello';

        $this->document->getLocale()->shouldNotBeCalled();
        $this->structureProperty->isLocalized()->willReturn(false);

        $this->doGetProperty($name, $contentTypeName, null);
    }

    private function doGetProperty($name, $contentTypeName, $locale)
    {
        $this->structureProperty->getType()->willReturn($contentTypeName);
        $this->structure->getProperty($name)->willReturn($this->structureProperty);
        $this->contentTypeManager->get($contentTypeName)->willReturn($this->contentType->reveal());

        if ($locale) {
            $this->propertyFactory->createTranslatedProperty($this->structureProperty->reveal(), $locale)->willReturn($this->legacyProperty->reveal());
        } else {
            $this->propertyFactory->createProperty($this->structureProperty->reveal(), $locale)->willReturn($this->legacyProperty->reveal());
        }


        $this->contentType->read(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            null,
            null,
            null
        )->shouldBeCalledTimes(1);

        $property = $this->propertyContainer->getProperty($name);

        $this->assertInstanceOf(PropertyValue::class, $property);
        $this->assertEquals($name, $property->getName());
    }
}
