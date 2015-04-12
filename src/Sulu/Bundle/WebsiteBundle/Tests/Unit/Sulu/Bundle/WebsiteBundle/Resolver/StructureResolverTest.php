<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Prophecy\Argument;
use Sulu\Component\Content\Type\ContentTypeInterface;
use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;

class StructureResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    /**
     * @var StructureFactoryInterface
     */
    private $structureFactory;

    public function setUp()
    {
        parent::setUp();

        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\Type\ContentTypeManagerInterface');
        $this->structureFactory = $this->prophesize('Sulu\Component\Content\Structure\Factory\StructureFactoryInterface');
        $this->contentType = $this->prophesize('Sulu\Component\Content\Type\ContentTypeInterface');

        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->structureFactory->reveal()
        );
    }

    public function testResolve()
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);

        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize('Sulu\Component\Content\Extension\AbstractExtension');
        $excerptExtension->getContentData(array('test1' => 'test1'))->willReturn(array('test1' => 'test1'));
        $this->structureFactory->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property = $this->prophesize('Sulu\Component\Content\Document\Property\PropertyInterface');
        $property->getName()->willReturn('property');
        $property->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize('Sulu\Component\Content\Structure\Page');
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(array('excerpt' => array('test1' => 'test1')));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn(array($property->reveal()));
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getPath()->willReturn('test-path');
        $structure->getUrls()->willReturn(array('en' => '/description', 'de' => '/beschreibung', 'es' => null));

        $expected = array(
            'extension' => array(
                'excerpt' => array('test1' => 'test1')
            ),
            'uuid' => 'some-uuid',
            'view' => array(
                'property' => 'view'
            ),
            'content' => array(
                'property' => 'content'
            ),
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => array('en' => '/description', 'de' => '/beschreibung', 'es' => null),
            'path' => 'test-path',
        );

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal()));
    }
}
