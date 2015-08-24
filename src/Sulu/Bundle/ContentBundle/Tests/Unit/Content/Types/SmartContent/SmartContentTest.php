<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\SmartContentContainer;
use Sulu\Component\SmartContent\ContentType as SmartContent;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
interface NodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

/**
 * @group unit
 */
class SmartContentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SmartContent
     */
    private $smartContent;

    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQuery;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        $this->contentQuery = $this->getMockForAbstractClass('Sulu\Component\Content\Query\ContentQueryExecutorInterface');
        $this->contentQueryBuilder = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Query\ContentQueryBuilderInterface'
        );

        $this->tagManager = $this->getMockForAbstractClass(
            'Sulu\Bundle\TagBundle\Tag\TagManagerInterface',
            [],
            '',
            false,
            true,
            true,
            ['resolveTagIds', 'resolveTagNames']
        );

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();

        $this->requestStack->expects($this->any())->method('getCurrentRequest')->will(
            $this->returnValue($this->request)
        );

        $this->smartContent = new SmartContent(
            $this->contentQuery,
            $this->contentQueryBuilder,
            $this->tagManager,
            $this->requestStack,
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $this->tagManager->expects($this->any())->method('resolveTagIds')->will(
            $this->returnValueMap(
                [
                    [[1, 2], ['Tag1', 'Tag2']],
                ]
            )
        );

        $this->tagManager->expects($this->any())->method('resolveTagName')->will(
            $this->returnValueMap(
                [
                    [['Tag1', 'Tag2'], [1, 2]],
                ]
            )
        );
    }

    public function testTemplate()
    {
        $this->assertEquals(
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->smartContent->getTemplate()
        );
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'dataSource' => [
                        'home/products',
                    ],
                    'sortBy' => [
                        'published',
                    ],
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                [
                    'dataSource' => [
                        'home/products',
                    ],
                    'sortBy' => [
                        'published',
                    ],
                ]
            )
        );

        $this->smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'config' => [
                        'dataSource' => [
                            'home/products',
                        ],
                        'sortBy' => [
                            'published',
                        ],
                    ],
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                [
                    'dataSource' => [
                        'home/products',
                    ],
                    'sortBy' => [
                        'published',
                    ],
                ]
            )
        );

        $this->smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $smartContentContainer = new SmartContentContainer(
            $this->contentQuery,
            $this->contentQueryBuilder,
            $this->tagManager,
            [
                'page_parameter' => 'p',
                'properties' => ['my_title' => 'title'],
            ],
            'test',
            'en',
            's'
        );
        $smartContentContainer->setConfig(
            [
                'tags' => ['Tag1', 'Tag2'],
                'limitResult' => '2',
            ]
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['setValue']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    ['property', '{}', '{"tags":[1,2],"limitResult":"2"}'],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));
        $property->expects($this->any())->method('getParams')->will($this->returnValue(['properties' => ['my_title' => 'title']]));

        $property->expects($this->exactly(1))->method('setValue')->with($smartContentContainer->getConfig());

        $this->smartContent->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreview()
    {
        $smartContentContainerPreview = new SmartContentContainer(
            $this->contentQuery,
            $this->contentQueryBuilder,
            $this->tagManager,
            [
                'page_parameter' => 'p',
                'properties' => [],
            ],
            'test', 'en', 's'
        );
        $smartContentContainerPreview->setConfig(
            [
                'tags' => ['Tag1', 'Tag2'],
                'limitResult' => '2',
            ]
        );

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['setValue']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    ['property', '{}', '{"tags":[1,2],"limitResult":"2"}'],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));
        $property->expects($this->any())->method('getParams')->will($this->returnValue([]));

        $property->expects($this->exactly(1))->method('setValue')->with($smartContentContainerPreview->getConfig());

        $this->smartContent->readForPreview(
            ['tags' => ['Tag1', 'Tag2'], 'limitResult' => 2],
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testGetViewData()
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $config = ['dataSource' => 'some-uuid'];

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => 1, 'hasNextPage' => true]));

        $property->expects($this->exactly(1))->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', '5')]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentQuery->expects($this->once())->method('execute')
            ->with(
                $this->equalTo(null),
                $this->equalTo([null]),
                $this->equalTo($this->contentQueryBuilder),
                $this->equalTo(true),
                $this->equalTo(-1),
                $this->equalTo(6),
                $this->equalTo(null)
            )->will($this->returnValue([1, 2, 3, 4, 5, 6]));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        $this->request->expects($this->any())->method('get')->will($this->returnValue(1));

        $viewData = $this->smartContent->getViewData($property);

        $this->assertContains(array_merge($config, ['page' => 1, 'hasNextPage' => true]), $viewData);
    }

    public function testGetContentData()
    {
        $property = $this->getContentDataProperty();
        $contentData = $this->smartContent->getContentData($property);

        $this->assertEquals(
            [
                ['uuid' => 1],
                ['uuid' => 2],
                ['uuid' => 3],
                ['uuid' => 4],
                ['uuid' => 5],
                ['uuid' => 6],
            ],
            $contentData
        );
    }

    public function testGetReferencedUuids()
    {
        $property = $this->getContentDataProperty();
        $uuids = $this->smartContent->getReferencedUuids($property);

        $this->assertEquals([1, 2, 3, 4, 5, 6], $uuids);
    }

    public function testGetContentDataPaged()
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->any())->method('get')->will($this->returnValue(1));

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue(['dataSource' => '123-123-123']));

        $property->expects($this->exactly(1))->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', '5')]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentQuery->expects($this->once())->method('execute')
            ->with(
                $this->equalTo(null),
                $this->equalTo([null]),
                $this->equalTo($this->contentQueryBuilder),
                $this->equalTo(true),
                $this->equalTo(-1),
                $this->equalTo(6),
                $this->equalTo(0)
            )->will($this->returnValue([1, 2, 3, 4, 5, 6]));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        $contentData = $this->smartContent->getContentData($property);

        $this->assertEquals([1, 2, 3, 4, 5], $contentData);
    }

    public function pageProvider()
    {
        return [
            // first page page-size 3 (one page more to check available pages)
            [1, 3, 0, 8, '123-123-123', [1, 2, 3, 4], [1, 2, 3], 4, true],
            // second page page-size 3 (one page more to check available pages)
            [2, 3, 3, 8, '123-123-123', [4, 5, 6, 7], [4, 5, 6], 4, true],
            // third page page-size 3 (only two pages because of the limit-result)
            [3, 3, 6, 8, '123-123-123', [7, 8], [7, 8], 2, false],
            // fourth page page-size 3 (empty result)
            [4, 3, 6, 8, '123-123-123', [], [], null, false],
            // test empty string (should be ignored)
            [3, 3, 6, '', '123-123-123', [7, 8], [7, 8], 4, false],
        ];
    }

    /**
     * @dataProvider pageProvider
     */
    public function testGetContentDataPagedLimit($page, $pageSize, $offset, $limitResult, $uuid, $data, $expectedData, $limit, $hasNextPage)
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->any())->method('get')->will($this->returnValue($page));

        $config = ['limitResult' => $limitResult, 'dataSource' => '123-123-123'];

        if ($limit) {
            $this->contentQuery->expects($this->once())->method('execute')
                ->with(
                    $this->equalTo(null),
                    $this->equalTo([null]),
                    $this->equalTo($this->contentQueryBuilder),
                    $this->equalTo(true),
                    $this->equalTo(-1),
                    $this->equalTo($limit),
                    $this->equalTo($offset)
                )->will($this->returnValue($data));
        }
        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue($config));
        $property->expects($this->exactly(1))->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));

        $contentData = $this->smartContent->getContentData($property);
        $this->assertEquals($expectedData, $contentData);
    }

    /**
     * @dataProvider pageProvider
     */
    public function testGetViewDataPagedLimit($page, $pageSize, $offset, $limitResult, $uuid, $data, $expectedData, $limit, $hasNextPage)
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->any())->method('get')->will($this->returnValue($page));

        $config = ['limitResult' => $limitResult, 'dataSource' => '123-123-123'];

        if ($limit) {
            $this->contentQuery->expects($this->once())->method('execute')
                ->with(
                    $this->equalTo(null),
                    $this->equalTo([null]),
                    $this->equalTo($this->contentQueryBuilder),
                    $this->equalTo(true),
                    $this->equalTo(-1),
                    $this->equalTo($limit),
                    $this->equalTo($offset)
                )->will($this->returnValue($data));
        }

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => $page, 'hasNextPage' => $hasNextPage]));

        $property->expects($this->exactly(1))->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));

        $viewData = $this->smartContent->getViewData($property);
        $this->assertEquals(
            array_merge(
                [
                    'dataSource' => null,
                    'includeSubFolders' => null,
                    'category' => null,
                    'tags' => [],
                    'sortBy' => null,
                    'sortMethod' => null,
                    'presentAs' => null,
                    'limitResult' => null,
                    'page' => null,
                    'hasNextPage' => null,
                ],
                $config,
                ['page' => $page, 'hasNextPage' => $hasNextPage]
            ),
            $viewData);
    }

    private function getContentDataProperty()
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue(['dataSource' => '123-123-123']));

        $property->expects($this->exactly(1))->method('getParams')
            ->will($this->returnValue([]));

        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentQuery->expects($this->once())->method('execute')
            ->with(
                $this->equalTo(null),
                $this->equalTo([null]),
                $this->equalTo($this->contentQueryBuilder),
                $this->equalTo(true),
                $this->equalTo(-1),
                $this->equalTo(null),
                $this->equalTo(null)
            )->will($this->returnValue([
                ['uuid' => 1],
                ['uuid' => 2],
                ['uuid' => 3],
                ['uuid' => 4],
                ['uuid' => 5],
                ['uuid' => 6],
            ]));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        return $property;
    }
}
