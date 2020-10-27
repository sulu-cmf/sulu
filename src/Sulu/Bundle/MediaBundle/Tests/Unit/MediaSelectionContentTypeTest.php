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
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class MediaSelectionContentTypeTest extends TestCase
{
    /**
     * @var MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var ReferenceStoreInterface
     */
    private $mediaReferenceStore;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var MediaManagerInterface
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
            ['view' => 64]
        );
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                    'data' => ['data1', 'data2'],
                ]
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{"ids": []}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->once())->method('setValue')->with(\json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadWithInvalidValue()
    {
        $config = '[]';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{"ids": []}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->once())->method('setValue')->with(null)->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadWithType()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{"ids": []}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->once())->method('setValue')->with(\json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                    'types' => 'document',
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadWithMultipleTypes()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            NodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setValue', 'getParams']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    [
                        'property',
                        '{"ids": []}',
                        $config,
                    ],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->once())->method('setValue')->with(\json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                [
                    'types' => 'document,image',
                ]
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testGetContentData()
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

    public function testGetContentDataWithPermissions()
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

    public function testPreResolve()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);

        $this->mediaSelection->preResolve($property->reveal());

        $this->mediaReferenceStore->add(1)->shouldBeCalled();
        $this->mediaReferenceStore->add(2)->shouldBeCalled();
        $this->mediaReferenceStore->add(3)->shouldBeCalled();
    }
}
