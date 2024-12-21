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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class MediaSelectionContentTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $mediaReferenceStore;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private $mediaManager;

    protected function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->webspace = new Webspace();
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace);

        $this->mediaSelection = new MediaSelectionContentType(
            $this->mediaManager->reveal(),
            $this->mediaReferenceStore->reveal(),
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
            new PropertyMetadataMinMaxValueResolver()
        );
    }

    public function testWrite(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->getValue()->willReturn(
            [
                'ids' => [1, 2, 3, 4],
                'displayOption' => 'right',
                'config' => ['conf1' => 1, 'conf2' => 2],
            ]
        )->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        )->shouldBeCalled();

        $this->mediaSelection->write($node->reveal(), $property->reveal(), 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer(): void
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->getValue()->willReturn(
            [
                'ids' => [1, 2, 3, 4],
                'displayOption' => 'right',
                'config' => ['conf1' => 1, 'conf2' => 2],
                'data' => ['data1', 'data2'],
            ]
        )->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        )->shouldBeCalled();

        $this->mediaSelection->write($node->reveal(), $property->reveal(), 0, 'test', 'en', 's');
    }

    public function testRead(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithInvalidValue(): void
    {
        $config = '[]';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(null)->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithType(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();
        $property->getParams()->willReturn(['types' => 'document']);

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithMultipleTypes(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);
        $property->getParams()->willReturn([]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->mediaManager->getByIds([1, 2, 3], null, null)->shouldBeCalled();

        $result = $this->mediaSelection->getContentData($property->reveal());
    }

    public function testGetContentDataWithPermissions(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);
        $property->getParams()->willReturn([]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(true);
        $this->webspace->setSecurity($security);

        $this->mediaManager->getByIds([1, 2, 3], null, 64)->shouldBeCalled();

        $result = $this->mediaSelection->getContentData($property->reveal());
    }

    public function testPreResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);

        $this->mediaSelection->preResolve($property->reveal());

        $this->mediaReferenceStore->add(1)->shouldBeCalled();
        $this->mediaReferenceStore->add(2)->shouldBeCalled();
        $this->mediaReferenceStore->add(3)->shouldBeCalled();
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyArraySchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
            ],
            'maxItems' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'ids' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'number',
                    ],
                    'minItems' => 1,
                    'uniqueItems' => true,
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['ids'],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMax(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 3],
        ]);

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
        ]);

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMaxOnly(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 2],
        ]);

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'maxItems' => 2,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxWithIntegerishValues(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => '2'],
            ['name' => 'max', 'value' => '3'],
        ]);

        $jsonSchema = $this->mediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataMinAndMaxMinInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 'invalid-value'],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMinTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "min" of property "property-name" needs to be greater than or equal "0"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => -1],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMandatoryMinTooLow(): void
    {
        $this->expectExceptionMessage('Because property "property-name" is mandatory, parameter "min" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 0],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxInvalidType(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be either null or of type int');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 'invalid-value'],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxTooLow(): void
    {
        $this->expectExceptionMessage('Parameter "max" of property "property-name" needs to be greater than or equal "1"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'max', 'value' => 0],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testMapPropertyMetadataMinAndMaxMaxLowerThanMin(): void
    {
        $this->expectExceptionMessage('Because parameter "min" of property "property-name" has value "2", parameter "max" needs to be greater than or equal "2"');

        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setParameters([
            ['name' => 'min', 'value' => 2],
            ['name' => 'max', 'value' => 1],
        ]);

        $this->mediaSelection->mapPropertyMetadata($propertyMetadata);
    }

    public function testGetReferencesWithNullProperty(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'single_media_selection',
        );
        $property->setValue(null);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(Argument::cetera())->shouldNotHaveBeenCalled();

        $this->mediaSelection->getReferences($property, $referenceCollector->reveal());
    }

    public function testGetReferences(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'media_selection',
        );
        $property->setValue(['ids' => [1, 2]]);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(
            'media',
            1,
            'media'
        )->shouldBeCalled();
        $referenceCollector->addReference(
            'media',
            2,
            'media'
        )->shouldBeCalled();

        $this->mediaSelection->getReferences($property, $referenceCollector->reveal());
    }
}
