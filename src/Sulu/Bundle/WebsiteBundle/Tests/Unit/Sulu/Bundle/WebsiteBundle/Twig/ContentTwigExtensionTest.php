<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Webspace;

class TestStructure extends Structure
{
    public function __construct($uuid, $title, $userId)
    {
        parent::__construct('test', '', '');

        $this->setUuid($uuid);
        $this->setCreator($userId);
        $this->setChanger($userId);

        $this->addChild(new Property('title', array(), 'text_line'));
        $this->getProperty('title')->setValue($title);
    }
}

class ContentTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var NodeInterface
     */
    private $startPageNode;

    protected function setUp()
    {
        parent::setUp();

        $this->contentMapper = $this->prophesize('Sulu\Component\Content\Mapper\ContentMapperInterface');
        $this->requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');
        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\ContentTypeManagerInterface');
        $this->structureManager = $this->prophesize('Sulu\Component\Content\StructureManagerInterface');
        $this->sessionManager = $this->prophesize('Sulu\Component\PHPCR\SessionManager\SessionManagerInterface');
        $this->session = $this->prophesize('PHPCR\SessionInterface');
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $this->parentNode = $this->prophesize('PHPCR\NodeInterface');
        $this->startPageNode = $this->prophesize('PHPCR\NodeInterface');

        $webspace = new Webspace();
        $webspace->setKey('sulu_test');

        $locale = new Localization();
        $locale->setCountry('us');
        $locale->setLanguage('en');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($locale);

        $this->contentTypeManager->get('text_line')->willReturn(new TextLine(''));

        $this->sessionManager->getSession()->willReturn($this->session->reveal());
        $this->sessionManager->getContentNode('sulu_test')->willReturn($this->startPageNode->reveal());

        $this->session->getNodeByIdentifier('123-123-123')->willReturn($this->node->reveal());
        $this->session->getNodeByIdentifier('321-321-321')->willReturn($this->parentNode->reveal());

        $this->node->getIdentifier()->willReturn('123-123-123');
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(4);

        $this->parentNode->getIdentifier()->willReturn('321-321-321');
        $this->parentNode->getDepth()->willReturn(3);

        $this->startPageNode->getDepth()->willReturn(3);

        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->structureManager->reveal()
        );
    }

    public function testLoad()
    {
        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn(new TestStructure('123-123-123', 'test', 1));

        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver,
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $result = $extension->load('123-123-123');

        // uuid
        $this->assertEquals('123-123-123', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        // content
        $this->assertEquals(array('title' => 'test'), $result['content']);
        $this->assertEquals(array('title' => array()), $result['view']);
    }

    public function testLoadParent()
    {
        $this
            ->contentMapper
            ->load('321-321-321', 'sulu_test', 'en_us')
            ->willReturn(new TestStructure('321-321-321', 'test', 1));

        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver,
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $result = $extension->loadParent('123-123-123');

        // uuid
        $this->assertEquals('321-321-321', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        // content
        $this->assertEquals(array('title' => 'test'), $result['content']);
        $this->assertEquals(array('title' => array()), $result['view']);
    }

    public function testLoadParentStartPage()
    {
        $this->setExpectedException(
            'Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException',
            'Parent for "321-321-321" not found (perhaps it is the startpage?)'
        );

        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver,
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $extension->loadParent('321-321-321');
    }
}
