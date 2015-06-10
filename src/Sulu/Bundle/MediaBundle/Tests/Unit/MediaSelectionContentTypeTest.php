<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPUnit_Framework_TestCase;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
interface NodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

class MediaSelectionContentTypeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var \Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface
     */
    private $mediaManager;

    protected function setUp()
    {
        $this->mediaManager = $this->getMock('\Sulu\Bundle\MeediaBundle\Media\Manager\MediaManagerInterface');

        $this->mediaSelection = new \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType(
            $this->mediaManager, 'SuluMediaBundle:Template:image-selection.html.twig'
        );
    }

    public function testTemplate()
    {
        $this->assertEquals('SuluMediaBundle:Template:image-selection.html.twig', $this->mediaSelection->getTemplate());
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setProperty')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getValue', 'getParams')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2),
                )
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2),
                )
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setProperty')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getValue', 'getParams')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2),
                    'data' => array('data1', 'data2'),
                )
            )
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'ids' => array(1, 2, 3, 4),
                    'displayOption' => 'right',
                    'config' => array('conf1' => 1, 'conf2' => 2),
                )
            )
        );

        $this->mediaSelection->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreview()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will(
            $this->returnValue(null)
        );

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4),
            ),
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testReadWithType()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document',
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreviewWithType()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document',
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4),
            ),
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testReadWithMultipleTypes()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document,image',
                )
            )
        );

        $this->mediaSelection->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreviewMultipleTypes()
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getPropertyValueWithDefault')
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setValue', 'getParams')
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                array(
                    array(
                        'property',
                        '{}',
                        $config,
                    ),
                )
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('setValue')->with(json_decode($config, true))->will($this->returnValue(null));

        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(
                array(
                    'types' => 'document,image',
                )
            )
        );

        $this->mediaSelection->readForPreview(
            array(
                'config' => array('conf1' => 1, 'conf2' => 2),
                'displayOption' => 'right',
                'ids' => array(1, 2, 3, 4),
            ),
            $property,
            'test',
            'en',
            's'
        );
    }
}
