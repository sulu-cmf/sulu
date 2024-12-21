<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Block;

use Jackalope\Node;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\PageBundle\Content\Types\SinglePageSelection;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Types\Block\BlockVisitorInterface;
use Sulu\Component\Content\Types\BlockContentType;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class BlockContentTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var BlockContentType
     */
    private $blockContentType;

    /**
     * @var PropertyInterface
     */
    private $blockProperty;

    /**
     * @var PropertyInterface
     */
    private $subBlockProperty;

    /**
     * @var ObjectProphecy<Node>
     */
    private $node;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeValueMap;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<TargetGroupStoreInterface>
     */
    private $targetGroupStore;

    /**
     * @var ObjectProphecy<ContentTypeManager>
     */
    private $contentTypeManager;

    /**
     * @var ObjectProphecy<BlockVisitorInterface>
     */
    private $blockVisitor1;

    /**
     * @var ObjectProphecy<BlockVisitorInterface>
     */
    private $blockVisitor2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManager::class);
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
        $this->blockVisitor1 = $this->prophesize(BlockVisitorInterface::class);
        $this->blockVisitor2 = $this->prophesize(BlockVisitorInterface::class);

        $this->blockVisitor1->visit(Argument::any())->will(function($arguments) {return $arguments[0]; });
        $this->blockVisitor2->visit(Argument::any())->will(function($arguments) {return $arguments[0]; });

        $this->blockContentType = new BlockContentType(
            $this->contentTypeManager->reveal(),
            'not in use',
            $this->requestAnalyzer->reveal(),
            $this->targetGroupStore->reveal(),
            [
                $this->blockVisitor1->reveal(),
                $this->blockVisitor2->reveal(),
            ]
        );

        $this->contentTypeValueMap = [
            ['text_line', new TextLine()],
            ['text_area', new TextArea()],
            ['internal_link', new SinglePageSelection(new ReferenceStore())],
            ['block', $this->blockContentType],
        ];

        $this->contentTypeManager->get('text_line')->willReturn(new TextLine());
        $this->contentTypeManager->get('text_area')->willReturn(new TextArea());
        $this->contentTypeManager->get('internal_link')->willReturn(new SinglePageSelection(new ReferenceStore()));
        $this->contentTypeManager->get('block')->willReturn($this->blockContentType);
    }

    protected function prepareSingleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 999, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area', false, true));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true, 999, 1);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));

        $this->blockProperty->addType($type2);
    }

    protected function prepareMultipleBlockProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 10, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area'));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));

        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));

        $this->blockProperty->addType($type2);
    }

    protected function prepareMultipleBlockWithLinksProperty()
    {
        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 10, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('title', '', 'text_line', false, true));
        $type1->addChild(new Property('article', '', 'text_area'));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('title', '', 'text_line', false, true));
        $subType1->addChild(new Property('article', '', 'text_area', false, true));
        $subType1->addChild(new Property('link', '', 'internal_link', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $type2 = new BlockPropertyType('type2', '');
        $type2->addChild(new Property('name', '', 'text_line', false, true));
        $type2->addChild(new Property('link', '', 'internal_link', false, true));

        $this->blockProperty->addType($type2);
    }

    public function testRead(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Sub-Title',
                        'article' => 'Test-Sub-Article',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 1,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadWithoutSettings(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Sub-Title',
                        'article' => 'Test-Sub-Article',
                    ],
                ],
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 1,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => null,
            'i18n:de-block1-sub-block#0-settings#0' => null,
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'Test-Title',
                    'article' => 'Test-Article',
                    'settings' => new \stdClass(),
                    'sub-block' => [
                        [
                            'type' => 'subType1',
                            'title' => 'Test-Sub-Title',
                            'article' => 'Test-Sub-Article',
                            'settings' => new \stdClass(),
                        ],
                    ],
                ],
            ],
            $this->blockProperty->getValue()
        );
    }

    public function testReadWithEmptySettings(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Sub-Title',
                        'article' => 'Test-Sub-Article',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 1,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'Test-Title',
                    'article' => 'Test-Article',
                    'sub-block' => [
                        [
                            'type' => 'subType1',
                            'title' => 'Test-Sub-Title',
                            'article' => 'Test-Sub-Article',
                            'settings' => new \stdClass(),
                        ],
                    ],
                    'settings' => new \stdClass(),
                ],
            ],
            $this->blockProperty->getValue()
        );
    }

    public function testWrite(): void
    {
        $this->prepareSingleBlockProperty();

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title',
                'article' => 'Test-Article',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title',
                        'article' => 'Test-Article',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 1,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultiple(): void
    {
        $this->prepareMultipleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => ['segments' => ['default' => 'w', 'other' => 's']],
            ],
            [
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-2',
                        'article' => 'Test-Article-Sub-2',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 2,
            'i18n:de-block1-type#0' => 'type1',
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => 'subType1',
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-type#1' => 'type1',
            'i18n:de-block1-title#1' => $data[1]['title'],
            'i18n:de-block1-article#1' => $data[1]['article'],
            'i18n:de-block1-sub-block#1-length' => 1,
            'i18n:de-block1-sub-block#1-type#0' => 'subType1',
            'i18n:de-block1-sub-block#1-title#0' => $data[1]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#1-article#0' => $data[1]['sub-block'][0]['article'],
            'i18n:de-block1-settings#0' => '{"segments": {"default": "w", "other": "s"}}',
            'i18n:de-block1-sub-block#0-settings#0' => '{}',
            'i18n:de-block1-settings#1' => '{}',
            'i18n:de-block1-sub-block#1-settings#0' => '{}',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testWriteMultiple(): void
    {
        $this->prepareMultipleBlockProperty();

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type1',
                'title' => 'Test-Title-2',
                'article' => 'Test-Article-2',
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-2',
                        'article' => 'Test-Article-Sub-2',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 2,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-type#1' => $data[1]['type'],
                'i18n:de-block1-title#1' => $data[1]['title'],
                'i18n:de-block1-article#1' => $data[1]['article'],
                'i18n:de-block1-sub-block#1-length' => 1,
                'i18n:de-block1-sub-block#1-type#0' => $data[1]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#1-title#0' => $data[1]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#1-article#0' => $data[1]['sub-block'][0]['article'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
                'i18n:de-block1-settings#1' => '[]',
                'i18n:de-block1-sub-block#1-settings#0' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testReadMultipleDifferentTypes(): void
    {
        $this->prepareMultipleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => new \stdClass(),
                    ],
                ],
                'settings' => new \stdClass(),
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => new \stdClass(),
            ],
        ];

        $valueMap = [
            'i18n:de-block1-length' => 2,
            'i18n:de-block1-type#0' => $data[0]['type'],
            'i18n:de-block1-title#0' => $data[0]['title'],
            'i18n:de-block1-article#0' => $data[0]['article'],
            'i18n:de-block1-sub-block#0-length' => 1,
            'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
            'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
            'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
            'i18n:de-block1-type#1' => $data[1]['type'],
            'i18n:de-block1-name#1' => $data[1]['name'],
            'i18n:de-block1-settings#0' => '[]',
            'i18n:de-block1-sub-block#0-settings#0' => '[]',
            'i18n:de-block1-settings#1' => '[]',
        ];

        $this->node = $this->prophesize(Node::class);
        foreach ($valueMap as $name => $value) {
            $this->node->getPropertyValue($name)->willReturn($value);
            $this->node->hasProperty($name)->willReturn(true);
        }

        $this->blockContentType->read(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            'default',
            'de',
            ''
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testWriteMultipleDifferentTypes(): void
    {
        $this->prepareMultipleBlockProperty();

        $result = [];
        $this->node = $this->prophesize(Node::class);
        $this->node->getPropertyValueWithDefault(Argument::any(), null)->willReturn(null);
        $this->node->setProperty(Argument::cetera())->will(
            function($arguments) use (&$result) {
                $result[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $this->blockContentType->write(
            $this->node->reveal(),
            new TranslatedProperty($this->blockProperty, 'de', 'i18n'),
            1,
            'default',
            'de',
            ''
        );

        // check repository node
        $this->assertEquals(
            [
                'i18n:de-block1-length' => 2,
                'i18n:de-block1-type#0' => $data[0]['type'],
                'i18n:de-block1-title#0' => $data[0]['title'],
                'i18n:de-block1-article#0' => $data[0]['article'],
                'i18n:de-block1-sub-block#0-length' => 1,
                'i18n:de-block1-sub-block#0-type#0' => $data[0]['sub-block'][0]['type'],
                'i18n:de-block1-sub-block#0-title#0' => $data[0]['sub-block'][0]['title'],
                'i18n:de-block1-sub-block#0-article#0' => $data[0]['sub-block'][0]['article'],
                'i18n:de-block1-type#1' => $data[1]['type'],
                'i18n:de-block1-name#1' => $data[1]['name'],
                'i18n:de-block1-settings#0' => '[]',
                'i18n:de-block1-sub-block#0-settings#0' => '[]',
                'i18n:de-block1-settings#1' => '[]',
            ],
            $result
        );

        // check resulted structure
        $this->assertEquals($data, $this->blockProperty->getValue());
    }

    public function testGetContentData(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $result = $this->blockContentType->getContentData($this->blockProperty);

        $this->assertEquals($data, $result);
    }

    public function testGetContentDataWithSkips(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    [
                        'type' => 'subType1',
                        'title' => 'Test-Title-Sub-1',
                        'article' => 'Test-Article-Sub-1',
                        'settings' => [],
                    ],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-3',
                'settings' => [],
            ],
        ];

        $this->blockProperty->setValue($data);
        $blockPropertyType1 = $this->blockProperty->getProperties(0);
        $blockPropertyType2 = $this->blockProperty->getProperties(1);
        $blockPropertyType3 = $this->blockProperty->getProperties(2);

        $this->blockVisitor1->visit($blockPropertyType1)->willReturn(null);
        $this->blockVisitor1->visit($blockPropertyType2)->willReturn($blockPropertyType2);
        $this->blockVisitor1->visit($blockPropertyType3)->willReturn($blockPropertyType3);
        $this->blockVisitor2->visit($blockPropertyType1)->willReturn($blockPropertyType1);
        $this->blockVisitor2->visit($blockPropertyType2)->willReturn(null);
        $this->blockVisitor2->visit($blockPropertyType3)->willReturn($blockPropertyType3);

        $result = $this->blockContentType->getContentData($this->blockProperty);

        $this->assertEquals([['type' => 'type2', 'name' => 'Test-Name-3', 'settings' => []]], $result);
    }

    public function testGetViewData(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1',
                    'settings' => [],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
        ];
        $this->blockProperty->setValue($data);

        $result = $this->blockContentType->getViewData($this->blockProperty);
        $this->assertEquals(
            [
                [
                    'title' => [],
                    'article' => [],
                    'sub-block' => [
                        [
                            'title' => [],
                            'article' => [],
                        ],
                    ],
                ],
                [
                    'name' => [],
                ],
            ],
            $result
        );
    }

    public function testGetViewDataWithSkips(): void
    {
        $this->prepareSingleBlockProperty();

        $data = [
            [
                'type' => 'type1',
                'title' => 'Test-Title-1',
                'article' => [
                    'Test-Article-1-1',
                    'Test-Article-1-2',
                ],
                'sub-block' => [
                    'type' => 'subType1',
                    'title' => 'Test-Title-Sub-1',
                    'article' => 'Test-Article-Sub-1',
                    'settings' => [],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-2',
                'settings' => [],
            ],
            [
                'type' => 'type2',
                'name' => 'Test-Name-3',
                'settings' => [],
            ],
        ];

        $this->blockProperty->setValue($data);
        $blockPropertyType1 = $this->blockProperty->getProperties(0);
        $blockPropertyType2 = $this->blockProperty->getProperties(1);
        $blockPropertyType3 = $this->blockProperty->getProperties(2);

        $this->blockVisitor1->visit($blockPropertyType1)->willReturn(null);
        $this->blockVisitor1->visit($blockPropertyType2)->willReturn($blockPropertyType2);
        $this->blockVisitor1->visit($blockPropertyType3)->willReturn($blockPropertyType3);
        $this->blockVisitor2->visit($blockPropertyType1)->willReturn($blockPropertyType1);
        $this->blockVisitor2->visit($blockPropertyType2)->willReturn(null);
        $this->blockVisitor2->visit($blockPropertyType3)->willReturn($blockPropertyType3);

        $result = $this->blockContentType->getViewData($this->blockProperty);
        $this->assertEquals(
            [
                [
                    'name' => [],
                ],
            ],
            $result
        );
    }

    public function testGetReferencesWithNullProperty(): void
    {
        $mediaSelectionContentType = new MediaSelectionContentType(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            null,
            null,
            null
        );
        $this->contentTypeManager->get('media_selection')->willReturn($mediaSelectionContentType);
        $singleMediaSelection = new SingleMediaSelection(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            $this->prophesize(RequestAnalyzerInterface::class)->reveal(),
            null,
        );
        $this->contentTypeManager->get('single_media_selection')->willReturn($singleMediaSelection);

        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 999, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('media', '', 'media_selection', false, true));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true, 999, 1);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('single_media', '', 'single_media_selection', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $this->blockProperty->setValue(null);

        /** @var ObjectProphecy<ReferenceCollectorInterface> $referenceCollector */
        $referenceCollector = $this->prophesize(ReferenceCollectorInterface::class);
        $this->blockContentType->getReferences($this->blockProperty, $referenceCollector->reveal());

        $referenceCollector->addReference(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testGetReferences(): void
    {
        $mediaSelectionContentType = new MediaSelectionContentType(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            null,
            null,
            null
        );
        $this->contentTypeManager->get('media_selection')->willReturn($mediaSelectionContentType);
        $singleMediaSelection = new SingleMediaSelection(
            $this->prophesize(MediaManagerInterface::class)->reveal(),
            new ReferenceStore(),
            $this->prophesize(RequestAnalyzerInterface::class)->reveal(),
            null,
        );
        $this->contentTypeManager->get('single_media_selection')->willReturn($singleMediaSelection);

        $this->blockProperty = new BlockProperty('block1', '', 'type1', false, true, 999, 1);
        $type1 = new BlockPropertyType('type1', '');
        $type1->addChild(new Property('media', '', 'media_selection', false, true));
        $this->blockProperty->addType($type1);

        $this->subBlockProperty = new BlockProperty('sub-block', '', 'subType1', false, true, 999, 1);
        $subType1 = new BlockPropertyType('subType1', '');
        $subType1->addChild(new Property('single_media', '', 'single_media_selection', false, true));
        $this->subBlockProperty->addType($subType1);
        $type1->addChild($this->subBlockProperty);

        $data = [
            [
                'type' => 'type1',
                'media' => [
                    'ids' => [
                        1,
                        2,
                    ],
                ],
                'sub-block' => [
                    'type' => 'subType1',
                    'single_media' => ['id' => 3],
                    'settings' => [],
                ],
                'settings' => [],
            ],
            [
                'type' => 'type1',
                'media' => [
                    'ids' => [
                        4,
                    ],
                ],
                'sub-block' => [],
                'settings' => [],
            ],
        ];

        $this->blockProperty->setValue($data);

        /** @var ObjectProphecy<ReferenceCollectorInterface> $referenceCollector */
        $referenceCollector = $this->prophesize(ReferenceCollectorInterface::class);
        $referenceCollector->addReference(
            'media',
            1,
            'block1[0].media'
        )
            ->shouldBeCalled()
            ->willReturn(new Reference());
        $referenceCollector->addReference(
            'media',
            2,
            'block1[0].media'
        )
            ->shouldBeCalled()
            ->willReturn(new Reference());
        $referenceCollector->addReference(
            'media',
            3,
            'block1[0].sub-block[0].single_media'
        )
            ->shouldBeCalled()
            ->willReturn(new Reference());
        $referenceCollector->addReference(
            'media',
            4,
            'block1[1].media'
        )
            ->shouldBeCalled()
            ->willReturn(new Reference());

        $this->blockContentType->getReferences($this->blockProperty, $referenceCollector->reveal());
    }
}
