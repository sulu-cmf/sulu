<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Mapper\Translation;

use Jackalope\Node;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Util\SuluNodeHelper;

class SuluNodeHelperTest extends TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property1;

    /**
     * @var PropertyInterface
     */
    private $property2;

    /**
     * @var PropertyInterface
     */
    private $property3;

    /**
     * @var PropertyInterface
     */
    private $property4;

    /**
     * @var PropertyInterface
     */
    private $property5;

    /**
     * @var PropertyInterface
     */
    private $property6;

    /**
     * @var PropertyInterface
     */
    private $property7;

    /**
     * @var SuluNodeHelper
     */
    private $helper;

    public function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)->disableOriginalConstructor()->getMock();
        $this->node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $this->structureMetadataFactory = $this->getMockBuilder(StructureMetadataFactoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->property1 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property2 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property3 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property4 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property5 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property6 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property7 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();

        $propertyIndex = 1;
        foreach ([
            'i18n:fr-changer' => 'One title',
            'bas:barfoo' => 'Two title',
            'i18n:it-barfoo' => 'Three title',
            'i18n:de-changer' => 'Four title',
            'i18n:de-bbbaaaa' => 'Five title',
            'i18n:de-seo-changer' => 'Six title',
            'i18n:de-de-changer' => 'Seven title',
        ] as $propertyName => $propertyValue) {
            $this->{'property' . $propertyIndex}->expects($this->any())
                ->method('getName')
                ->willReturn($propertyName);
            $this->{'property' . $propertyIndex}->expects($this->any())
                ->method('getValue')
                ->willReturn($propertyValue);
            ++$propertyIndex;
        }

        $this->node->expects($this->any())
            ->method('getProperties')
            ->willReturn(new \ArrayIterator([
                $this->property1,
                $this->property2,
                $this->property3,
                $this->property4,
                $this->property5,
                $this->property6,
                $this->property7,
            ]));

        $this->helper = new SuluNodeHelper(
            $this->session,
            'i18n',
            [
                'base' => 'cmf',
                'snippet' => 'snippets',
            ],
            $this->structureMetadataFactory
        );
    }

    public function testGetLanguagesForNode(): void
    {
        $languages = $this->helper->getLanguagesForNode($this->node);

        // languages are only counted if they are on the "template" property
        $this->assertEquals(['fr', 'de'], $languages);
    }

    public function testLocalizedPropertyValues(): void
    {
        $localizedValues = $this->helper->getLocalizedPropertyValues($this->node, 'changer');

        // languages are only counted if they are on the "template" property
        $this->assertEquals([
            'fr' => 'One title',
            'de' => 'Four title',
        ], $localizedValues);
    }

    public static function provideExtractWebspaceFromPath()
    {
        return [
            ['/cmf/sulu_io/content/articles/article-one', 'sulu_io'],
            ['/cmfcontent/articles/article-one', null],
            ['/cmf/webspace_five', null],
            ['/cmf/webspace_five/foo/bar/dar/ding', 'webspace_five'],
            ['', null],
            ['asdasd', null],
            ['/cmf/sulu-io/content/articles', 'sulu-io'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideExtractWebspaceFromPath')]
    public function testExtractWebspaceFromPath($path, $expected): void
    {
        $res = $this->helper->extractWebspaceFromPath($path);
        $this->assertEquals($expected, $res);
    }

    public static function provideExtractSnippetTypeFromPath()
    {
        return [
            ['/cmf/snippets/foobar/snippet1', 'foobar'],
            ['/cmf/snippets/bar-foo/snippet2', 'bar-foo'],
            ['/cmf', null, false],
            ['/cmf/snippets', null, false],
            ['/cmf/snippets/bar', null, false],
            ['/cmf/snippets/animal/elephpant', 'animal'],
            ['', null, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideExtractSnippetTypeFromPath')]
    public function testExtractSnippetTypeFromPath($path, $expected, $valid = true): void
    {
        if (false === $valid) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $res = $this->helper->extractSnippetTypeFromPath($path);
        $this->assertEquals($expected, $res);
    }

    public static function provideGetStructureTypeForNode()
    {
        return [
            ['sulu:snippet', 'snippet'],
            ['sulu:page', 'page'],
            ['sulu:foobar', null],
            ['', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetStructureTypeForNode')]
    public function testGetStructureTypeForNode($nodeType, $expected): void
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->willReturn([$nodeType]);

        $this->assertEquals($expected, $this->helper->getStructureTypeForNode($this->node));
    }

    public static function provideHasSuluNodeType()
    {
        return [
            ['sulu:snippet', true],
            [['sulu:foobar', 'sulu:snippet'], true],
            ['sulu:page', false],
            ['sulu:foobar', false],
            ['', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideHasSuluNodeType')]
    public function testHasSuluNodeType($nodeTypes, $expected): void
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->willReturn(['sulu:snippet']);

        $this->assertEquals($expected, $this->helper->hasSuluNodeType($this->node, $nodeTypes));
    }

    public function testSiblingNodes(): void
    {
        for ($i = 1; $i <= 3; ++$i) {
            ${'node' . $i} = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
            ${'node' . $i}->expects($this->any())
                ->method('getPath')
                ->willReturn('/foobar/foobar-' . $i);
        }
        $iterator = new \ArrayIterator([
            $node1, $node2, $node3,
        ]);
        $node2->expects($this->any())
            ->method('getParent')
            ->willReturn($this->node);
        $this->node->expects($this->any())
            ->method('getNodes')
            ->willReturn($iterator);

        $res = $this->helper->getNextNode($node2);
        $this->assertSame($node3->getPath(), $res->getPath());

        $iterator->rewind();
        $res = $this->helper->getPreviousNode($node2);
        $this->assertSame($node1->getPath(), $res->getPath());
    }

    public function testGetBaseSnippetUuid(): void
    {
        $baseSnippetNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $baseSnippetNode->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('some-uuid');

        $this->session->expects($this->any())
            ->method('getNode')
            ->with('/cmf/snippets/snippet')
            ->willReturn($baseSnippetNode);

        $this->assertEquals('some-uuid', $this->helper->getBaseSnippetUuid('snippet'));
    }
}
