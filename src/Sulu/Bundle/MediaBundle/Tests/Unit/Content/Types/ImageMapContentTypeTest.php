<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata as SchemaPropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Content\Types\ImageMapContentType;
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyType;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class ImageMapContentTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ImageMapContentType
     */
    private $imageMapContentType;

    /**
     * @var ObjectProphecy<TextLine>
     */
    private $textLineContentType;

    /**
     * @var ObjectProphecy<SingleMediaSelection>
     */
    private $singleMediaSelectionContentType;

    /**
     * @var ObjectProphecy<ContentTypeManagerInterface>
     */
    private $contentTypeManager;

    /**
     * @var ObjectProphecy<FormMetadataMapper>
     */
    private $formMetadataMapper;

    protected function setUp(): void
    {
        $this->textLineContentType = $this->prophesize(TextLine::class);
        $this->singleMediaSelectionContentType = $this->prophesize(SingleMediaSelection::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->formMetadataMapper = $this->prophesize(FormMetadataMapper::class);

        $this->contentTypeManager->get('text_line')->willReturn($this->textLineContentType);
        $this->contentTypeManager->get('single_media_selection')->willReturn($this->singleMediaSelectionContentType);

        $this->imageMapContentType = new ImageMapContentType(
            $this->contentTypeManager->reveal(),
            $this->formMetadataMapper->reveal(),
        );
    }

    public function testRead(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'imageId' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-imageId';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue($value['imageId']);
        })->shouldBeCalled();

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'length' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-length';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue(\count($value['hotspots']));
        })->shouldBeCalled();

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#0';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue($value['hotspots'][0]['type']);
        })->shouldBeCalled();

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#0';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue(\json_encode($value['hotspots'][0]['hotspot']));
        })->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][0]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->read(
                $node->reveal(),
                Argument::that(function($blockProperty) use ($property, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#0';
                }),
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->will(function($arguments) use ($value, $childName): void {
                [$node, $property] = $arguments;
                $property->setValue($value['hotspots'][0][$childName]);
            })->shouldBeCalled();
        }

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#1';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue($value['hotspots'][1]['type']);
        })->shouldBeCalled();

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#1';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($arguments) use ($value): void {
            [$node, $property] = $arguments;
            $property->setValue(\json_encode($value['hotspots'][1]['hotspot']));
        })->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][1]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->read(
                $node->reveal(),
                Argument::that(function($blockProperty) use ($property, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#1';
                }),
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->will(function($arguments) use ($value, $childName): void {
                [$node, $property] = $arguments;
                $property->setValue($value['hotspots'][1][$childName]);
            })->shouldBeCalled();
        }

        $this->imageMapContentType->read($node->reveal(), $property, $webspaceKey, $languageCode, $segmentKey);

        $this->assertEquals($value, $property->getValue());
    }

    public function testHasValue(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        $this->textLineContentType->hasValue(
            $node->reveal(),
            Argument::that(function($blockProperty) use ($property) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'imageId' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-imageId';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->willReturn(true);

        $this->assertTrue(
            $this->imageMapContentType->hasValue(
                $node->reveal(),
                $property,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )
        );
    }

    public function testWrite(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $userId = 1;
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $property->setValue($value);

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'imageId' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-imageId'
                    && $blockProperty->getValue() === $value['imageId'];
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'length' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-length'
                    && $blockProperty->getValue() === \count($value['hotspots']);
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#0'
                    && $blockProperty->getValue() === $value['hotspots'][0]['type'];
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#0'
                    && $blockProperty->getValue() === \json_encode($value['hotspots'][0]['hotspot']);
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][0]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->write(
                Argument::type(SuluNode::class),
                Argument::that(function($blockProperty) use ($property, $value, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#0'
                        && $blockProperty->getValue() === $value['hotspots'][0][$childName];
                }),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();
        }

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#1'
                    && $blockProperty->getValue() === $value['hotspots'][1]['type'];
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#1'
                    && $blockProperty->getValue() === \json_encode($value['hotspots'][1]['hotspot']);
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][1]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->write(
                Argument::type(SuluNode::class),
                Argument::that(function($blockProperty) use ($property, $value, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#1'
                        && $blockProperty->getValue() === $value['hotspots'][1][$childName];
                }),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();
        }

        $this->imageMapContentType->write(
            $node->reveal(),
            $property,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    public function testRemove(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        $nodeProperty1 = $this->prophesize(PropertyInterface::class);
        $nodeProperty1->getName()->willReturn('property1');
        $node->getProperty('property1')->willReturn($nodeProperty1->reveal());

        $nodeProperty2 = $this->prophesize(PropertyInterface::class);
        $nodeProperty2->getName()->willReturn('property2');
        $node->getProperty('property2')->willReturn($nodeProperty2->reveal());

        $node->getProperties($property->getName() . '-*')->willReturn([
            $nodeProperty1->reveal(),
            $nodeProperty2->reveal(),
        ]);

        $nodeProperty1->remove()->shouldBeCalled();
        $nodeProperty2->remove()->shouldBeCalled();

        $this->imageMapContentType->remove($node->reveal(), $property, $webspaceKey, $languageCode, $segmentKey);
    }

    public function testImportData(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $userId = 1;
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'imageId' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-imageId'
                    && $blockProperty->getValue() === $value['imageId'];
            }),
            $value['imageId'],
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'length' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-length'
                    && $blockProperty->getValue() === \count($value['hotspots']);
            }),
            \count($value['hotspots']),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#0'
                    && $blockProperty->getValue() === $value['hotspots'][0]['type'];
            }),
            $value['hotspots'][0]['type'],
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#0'
                    && $blockProperty->getValue() === \json_encode($value['hotspots'][0]['hotspot']);
            }),
            \json_encode($value['hotspots'][0]['hotspot']),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][0]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->importData(
                Argument::type(SuluNode::class),
                Argument::that(function($blockProperty) use ($property, $value, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#0'
                        && $blockProperty->getValue() === $value['hotspots'][0][$childName];
                }),
                $value['hotspots'][0][$childName],
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();
        }

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'type' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-type#1'
                    && $blockProperty->getValue() === $value['hotspots'][1]['type'];
            }),
            $value['hotspots'][1]['type'],
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($blockProperty) use ($property, $value) {
                return $blockProperty instanceof BlockPropertyWrapper
                    && 'hotspot' === $blockProperty->getProperty()->getName()
                    && $blockProperty->getBlock() === $property
                    && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-hotspot#1'
                    && $blockProperty->getValue() === \json_encode($value['hotspots'][1]['hotspot']);
            }),
            \json_encode($value['hotspots'][1]['hotspot']),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][1]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->importData(
                Argument::type(SuluNode::class),
                Argument::that(function($blockProperty) use ($property, $value, $childName) {
                    return $blockProperty instanceof BlockPropertyWrapper
                        && $blockProperty->getProperty()->getName() === $childName
                        && $blockProperty->getBlock() === $property
                        && $blockProperty->getName() === $blockProperty->getBlock()->getName() . '-' . $childName . '#1'
                        && $blockProperty->getValue() === $value['hotspots'][1][$childName];
                }),
                $value['hotspots'][1][$childName],
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();
        }

        $this->imageMapContentType->importData(
            $node->reveal(),
            $property,
            $value,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    public function testExportData(): void
    {
        $this->assertSame('foo', $this->imageMapContentType->exportData('foo'));
    }

    public function testGetContentData(): void
    {
        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $image = $this->prophesize(Media::class);

        $this->singleMediaSelectionContentType->getContentData(
            Argument::that(function($property) use ($value) {
                return $property instanceof Property
                    && 'image' === $property->getName()
                    && $property->getValue() === ['id' => $value['imageId']];
            })
        )->willReturn($image->reveal())->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][0]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->getContentData(
                Argument::that(function($property) use ($childName, $value) {
                    return $property instanceof Property
                        && $property->getName() === $childName
                        && $property->getValue() === $value['hotspots'][0][$childName];
                })
            )->will(function($arguments) {
                return $arguments[0]->getValue();
            })->shouldBeCalled();
        }

        $propertyType = $types[$value['hotspots'][1]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->getContentData(
                Argument::that(function($property) use ($childName, $value) {
                    return $property instanceof Property
                        && $property->getName() === $childName
                        && $property->getValue() === $value['hotspots'][1][$childName];
                })
            )->will(function($arguments) {
                return $arguments[0]->getValue();
            })->shouldBeCalled();
        }

        $expectedContentData = [
            'image' => $image->reveal(),
            'hotspots' => $value['hotspots'],
        ];

        $property->setValue($value);

        $this->assertEquals(
            $expectedContentData,
            $this->imageMapContentType->getContentData($property)
        );
    }

    public function testGetContentDataEmptyValue(): void
    {
        $value = [
            'imageId' => null,
            'hotspots' => [],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        $this->singleMediaSelectionContentType->getContentData(
            Argument::that(function($property) use ($value) {
                return $property instanceof Property
                    && 'image' === $property->getName()
                    && $property->getValue() === ['id' => $value['imageId']];
            })
        )->willReturn(null)->shouldBeCalled();

        $property->setValue($value);

        $this->assertNull($this->imageMapContentType->getContentData($property));
    }

    public function testGetContentDataNullValue(): void
    {
        $value = null;

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        $this->singleMediaSelectionContentType->getContentData(
            Argument::that(function($property) {
                return $property instanceof Property
                    && 'image' === $property->getName()
                    && $property->getValue() === ['id' => null];
            })
        )->willReturn(null)->shouldBeCalled();

        $property->setValue($value);

        $this->assertNull($this->imageMapContentType->getContentData($property));
    }

    public function testGetViewData(): void
    {
        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $this->singleMediaSelectionContentType->getViewData(
            Argument::that(function($property) use ($value) {
                return $property instanceof Property
                    && 'image' === $property->getName()
                    && $property->getValue() === ['id' => $value['imageId']];
            })
        )->willReturn([])->shouldBeCalled();

        $propertyType = $types[$value['hotspots'][0]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->getViewData(
                Argument::that(function($property) use ($childName, $value) {
                    return $property instanceof Property
                        && $property->getName() === $childName
                        && $property->getValue() === $value['hotspots'][0][$childName];
                })
            )->willReturn([])->shouldBeCalled();
        }

        $propertyType = $types[$value['hotspots'][1]['type']];
        foreach ($propertyType['children'] as $childName => $childType) {
            $this->textLineContentType->getViewData(
                Argument::that(function($property) use ($childName, $value) {
                    return $property instanceof Property
                        && $property->getName() === $childName
                        && $property->getValue() === $value['hotspots'][1][$childName];
                })
            )->willReturn([])->shouldBeCalled();
        }

        $expectedViewData = [
            'image' => [],
            'hotspots' => \array_map(function($hotspot) {
                return \array_map(
                    function() {
                        return [];
                    },
                    \array_filter(
                        $hotspot,
                        function($key) {
                            return 'type' !== $key && 'hotspot' !== $key;
                        },
                        \ARRAY_FILTER_USE_KEY
                    )
                );
            }, $value['hotspots']),
        ];

        $property->setValue($value);

        $this->assertEquals(
            $expectedViewData,
            $this->imageMapContentType->getViewData($property)
        );
    }

    public function testPreResolve(): void
    {
        $types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $this->singleMediaSelectionContentType->preResolve(
            Argument::that(function($property) use ($value) {
                return $property instanceof Property
                    && 'image' === $property->getName()
                    && $property->getValue() === ['id' => $value['imageId']];
            })
        )->shouldBeCalled();

        $property->setValue($value);
        $this->imageMapContentType->preResolve($property);
    }

    public function testMapPropertyMetatada(): void
    {
        $types = [
            'headline' => [
                'isGlobalBlock' => true,
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $metadata = new ContentPropertyMetadata('imageMap');
        $metadata->setRequired(true);
        foreach ($types as $key => $config) {
            $type = new ComponentMetadata($key);

            $isGlobalBlock = $config['isGlobalBlock'] ?? false;
            if ($isGlobalBlock) {
                $type->addTag([
                    'name' => 'sulu.global_block',
                    'attributes' => [
                        'global_block' => $key,
                    ],
                ]);
            }

            foreach ($config['children'] ?? [] as $childName => $childType) {
                $itemMetadata = new ContentPropertyMetadata($childName);
                $type->addChild($itemMetadata);
            }

            if (!$isGlobalBlock) {
                $itemSchemaMetadata = new SchemaMetadata([
                    new SchemaPropertyMetadata('type', false),
                ]);
                $this->formMetadataMapper->mapSchema($type->getChildren())->willReturn($itemSchemaMetadata);
            }
            $metadata->addComponent($type);
        }

        $result = $this->imageMapContentType->mapPropertyMetadata($metadata);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'hotspots' => [
                    'type' => 'array',
                    'items' => [
                        'allOf' => [
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'headline',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    '$ref' => '#/definitions/headline',
                                ],
                            ],
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'text',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'required' => ['imageId', 'hotspots'],
        ], $result->toJsonSchema());
    }

    public function testMapPropertyMetatadaWithRequiredFalse(): void
    {
        $types = [
            'headline' => [
                'isGlobalBlock' => true,
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $metadata = new ContentPropertyMetadata('imageMap');
        $metadata->setRequired(false);
        foreach ($types as $key => $config) {
            $type = new ComponentMetadata($key);

            $isGlobalBlock = $config['isGlobalBlock'] ?? false;
            if ($isGlobalBlock) {
                $type->addTag([
                    'name' => 'sulu.global_block',
                    'attributes' => [
                        'global_block' => $key,
                    ],
                ]);
            }

            foreach ($config['children'] ?? [] as $childName => $childType) {
                $itemMetadata = new ContentPropertyMetadata($childName);
                $type->addChild($itemMetadata);
            }

            if (!$isGlobalBlock) {
                $itemSchemaMetadata = new SchemaMetadata([
                    new SchemaPropertyMetadata('type', false),
                ]);
                $this->formMetadataMapper->mapSchema($type->getChildren())->willReturn($itemSchemaMetadata);
            }
            $metadata->addComponent($type);
        }

        $result = $this->imageMapContentType->mapPropertyMetadata($metadata);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'hotspots' => [
                    'type' => 'array',
                    'items' => [
                        'allOf' => [
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'headline',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    '$ref' => '#/definitions/headline',
                                ],
                            ],
                            [
                                'if' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'const' => 'text',
                                        ],
                                    ],
                                    'required' => ['type'],
                                ],
                                'then' => [
                                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $result->toJsonSchema());
    }

    public function testGetReferenceImageMap(): void
    {
        $types = [
            'headline-image' => [
                'children' => [
                    'headline-image' => 'single_media_selection',
                ],
            ],
            'text-images' => [
                'children' => [
                    'text-images' => 'media_selection',
                ],
            ],
        ];

        $value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text-images',
                    'text-images' => ['ids' => [2, 3, 4]],
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline-image',
                    'headline-image' => ['id' => 5],
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
                [
                    'type' => 'headline-image',
                    'headline-image' => null,
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );
        $property->setValue($value);

        foreach ($types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $property->addType($type);
        }

        $singleMediaSelectionContentType = new SingleMediaSelection(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            $this->prophesize(RequestAnalyzerInterface::class)->reveal(),
            null
        );
        $this->contentTypeManager->get('single_media_selection')->willReturn($singleMediaSelectionContentType);
        $mediaSelectionContentType = new MediaSelectionContentType(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            $this->prophesize(RequestAnalyzerInterface::class)->reveal(),
            null,
            null
        );
        $this->contentTypeManager->get('media_selection')->willReturn($mediaSelectionContentType);

        /** @var ObjectProphecy<ReferenceCollectorInterface> $referenceCollector */
        $referenceCollector = $this->prophesize(ReferenceCollectorInterface::class);
        // add image
        $referenceCollector->addReference(
            'media',
            1,
            'imageMap.image'
        )->shouldBeCalled()->willReturn(new Reference());

        $referenceCollector->addReference(
            'media',
            2,
            'imageMap.hotspots[0].text-images'
        )->shouldBeCalled()->willReturn(new Reference());

        $referenceCollector->addReference(
            'media',
            3,
            'imageMap.hotspots[0].text-images'
        )->shouldBeCalled()->willReturn(new Reference());

        $referenceCollector->addReference(
            'media',
            4,
            'imageMap.hotspots[0].text-images'
        )->shouldBeCalled()->willReturn(new Reference());

        $referenceCollector->addReference(
            'media',
            5,
            'imageMap.hotspots[1].headline-image'
        )->shouldBeCalled()->willReturn(new Reference());

        $this->imageMapContentType->getReferences($property, $referenceCollector->reveal());
    }
}
