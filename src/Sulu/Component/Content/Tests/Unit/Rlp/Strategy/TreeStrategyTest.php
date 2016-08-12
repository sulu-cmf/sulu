<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Rlp\Strategy;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorNotValidException;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Util\SuluNodeHelper;

class TreeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RlpMapperInterface
     */
    private $mapper;

    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var TreeStrategy
     */
    private $treeStrategy;

    public function setUp()
    {
        $this->mapper = $this->prophesize(RlpMapperInterface::class);
        $this->cleaner = $this->prophesize(PathCleanupInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);

        $this->treeStrategy = new TreeStrategy(
            $this->mapper->reveal(),
            $this->cleaner->reveal(),
            $this->structureManager->reveal(),
            $this->contentTypeManager->reveal(),
            $this->nodeHelper->reveal(),
            $this->documentInspector->reveal(),
            $this->documentManager->reveal()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('whole-tree', $this->treeStrategy->getName());
    }

    public function testGetChildPart()
    {
        $this->assertEquals('asdf', $this->treeStrategy->getChildPart('/test/asdf'));
        $this->assertEquals('asdf', $this->treeStrategy->getChildPart('/asdf'));
        $this->assertEquals('asdf', $this->treeStrategy->getChildPart('asdf'));
    }

    public function testGenerate()
    {
        $title = 'new-page';
        $parentUuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $parent = $this->prophesize(PageDocument::class);
        $parent->getPublished()->willReturn(true);

        $this->documentManager->find($parentUuid, $languageCode, ['load_ghost_content' => false])->willReturn($parent);
        $this->documentInspector->getUuid($parent)->willReturn($parentUuid);
        $this->mapper->loadByContentUuid($parentUuid, $webspaceKey, $languageCode, null)->willReturn('path/to/parent');
        $this->cleaner->cleanup('path/to/parent/new-page', $languageCode)->willReturn('path/to/parent/new-page');
        $this->mapper->getUniquePath('path/to/parent/new-page', $webspaceKey, $languageCode, null)->willReturn('path/to/parent/new-page');

        $result = $this->treeStrategy->generate($title, $parentUuid, $webspaceKey, $languageCode);
        $this->assertEquals('path/to/parent/new-page', $result);
    }

    public function testGenerateWithSegmentKey()
    {
        $title = 'new-page';
        $parentUuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = 'segment';

        $parent = $this->prophesize(PageDocument::class);
        $parent->getPublished()->willReturn(true);

        $this->documentManager->find($parentUuid, $languageCode, ['load_ghost_content' => false])->willReturn($parent);
        $this->documentInspector->getUuid($parent)->willReturn($parentUuid);
        $this->mapper->loadByContentUuid($parentUuid, $webspaceKey, $languageCode, null)->willReturn('path/to/parent');
        $this->cleaner->cleanup('path/to/parent/new-page', $languageCode)->willReturn('path/to/parent/new-page');
        $this->mapper->getUniquePath('path/to/parent/new-page', $webspaceKey, $languageCode, $segmentKey)->willReturn('path/to/parent/new-page');

        $result = $this->treeStrategy->generate($title, $parentUuid, $webspaceKey, $languageCode, $segmentKey);
        $this->assertEquals('path/to/parent/new-page', $result);
    }

    public function testGenerateUnpublishedParent()
    {
        $title = 'new-page';
        $parentUuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $grandParent = $this->prophesize(PageDocument::class);
        $grandParent->getPublished()->willReturn(true);
        $grandParentUuid = 'grand-parent-uuid-uuid';

        $parent = $this->prophesize(PageDocument::class);
        $parent->getPublished()->willReturn(false);
        $parent->getParent()->willReturn($grandParent->reveal());

        $this->documentManager->find($parentUuid, $languageCode, ['load_ghost_content' => false])->willReturn($parent);
        $this->documentInspector->getUuid($grandParent)->willReturn($grandParentUuid);
        $this->mapper->loadByContentUuid($grandParentUuid, $webspaceKey, $languageCode, null)->willReturn('path/to/grandparent');
        $this->cleaner->cleanup('path/to/grandparent/new-page', $languageCode)->willReturn('path/to/grandparent/new-page');
        $this->mapper->getUniquePath('path/to/grandparent/new-page', $webspaceKey, $languageCode, null)->willReturn('path/to/grandparent/new-page-1');

        $result = $this->treeStrategy->generate($title, $parentUuid, $webspaceKey, $languageCode);
        $this->assertEquals('path/to/grandparent/new-page-1', $result);
    }

    public function testGenerateWithoutParentUuid()
    {
        $title = 'new-page';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $parent = $this->prophesize(PageDocument::class);
        $parent->getPublished()->willReturn(true);

        $this->cleaner->cleanup('//new-page', $languageCode)->willReturn('/new-page');
        $this->mapper->getUniquePath('/new-page', $webspaceKey, $languageCode, null)->willReturn('path/to/parent/new-page');

        $result = $this->treeStrategy->generate($title, null, $webspaceKey, $languageCode);
        $this->assertEquals('path/to/parent/new-page', $result);
    }

    public function testSave()
    {
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $document->getChildren()->willReturn([]);

        $session = $this->prophesize(SessionInterface::class);
        $session->save()->shouldBeCalled();
        $node = $this->prophesize(NodeInterface::class);
        $node->getSession()->willReturn($session);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('old/path');
        $this->cleaner->validate('path/to/doc')->willReturn(true);
        $this->mapper->unique('path/to/doc', $webspaceKey, $languageCode)->willReturn(true);
        $this->mapper->save($document)->shouldBeCalled();

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testSaveSame()
    {
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $node = $this->prophesize(NodeInterface::class);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('path/to/doc');

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testSaveInvalid()
    {
        $this->setExpectedException(ResourceLocatorNotValidException::class);

        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $node = $this->prophesize(NodeInterface::class);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('old/path');
        $this->cleaner->validate('path/to/doc')->willReturn(false);

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testSaveAlreadyExist()
    {
        $this->setExpectedException(ResourceLocatorAlreadyExistsException::class);

        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $node = $this->prophesize(NodeInterface::class);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);
        $this->documentInspector->getUuid($document)->willReturn('document-uuid-uuid');

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('old/path');
        $this->cleaner->validate('path/to/doc')->willReturn(true);
        $this->mapper->unique('path/to/doc', $webspaceKey, $languageCode)->willReturn(false);
        $this->mapper->loadByResourceLocator('path/to/doc', $webspaceKey, $languageCode, null)->willReturn('other-uuid');

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testSaveWithPublishedChild()
    {
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $this->nodeHelper->getTranslatedPropertyName('template', $languageCode)->willReturn('template-prop');
        $structure = $this->prophesize(StructureInterface::class);
        $this->structureManager->getStructure('default')->willReturn($structure);

        $session = $this->prophesize(SessionInterface::class);
        $session->save()->shouldBeCalledTimes(2);

        $childDocument = $this->prophesize(PageDocument::class);
        $childDocument->getResourceSegment()->willReturn('path/to/olddoc/pub');
        $childDocument->getChildren()->willReturn([]);
        $childDocument->getPublished()->willReturn(true);

        $childNode = $this->prophesize(NodeInterface::class);
        $childNode->getSession()->willReturn($session);
        $childNode->getPropertyValue('template-prop')->willReturn('default');

        $this->documentInspector->getNode($childDocument)->willReturn($childNode);
        $this->documentInspector->getWebspace($childDocument)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($childDocument)->willReturn($languageCode);
        $this->documentInspector->getUuid($childDocument)->willReturn('published-child-uuid-uuid');

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $document->getChildren()->willReturn([$childDocument]);
        $document->getPublished()->willReturn(true);

        $node = $this->prophesize(NodeInterface::class);
        $node->getSession()->willReturn($session);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);
        $this->documentInspector->getUuid($document)->willReturn('uuid-uuid-uuid-uuid');

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('old/path');
        $this->cleaner->validate('path/to/doc')->willReturn(true);
        $this->mapper->unique('path/to/doc', $webspaceKey, $languageCode)->willReturn(true);
        $this->mapper->save($document)->shouldBeCalled();

        // adapt published child
        $this->documentManager->find('uuid-uuid-uuid-uuid', $languageCode, ['load_ghost_content' => false])->willReturn($document);
        $this->documentInspector->getUuid($document)->willReturn('uuid-uuid-uuid-uuid');
        $this->mapper->loadByContentUuid('uuid-uuid-uuid-uuid', $webspaceKey, $languageCode, null)->willReturn('path/to/doc');
        $this->cleaner->cleanup('path/to/doc/pub', $languageCode)->willReturn('path/to/doc/pub');
        $this->mapper->getUniquePath('path/to/doc/pub', $webspaceKey, $languageCode, null)->willReturn('path/to/doc/pub');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getContentTypeName()->willReturn('content-type');
        $structure->getPropertyByTagName('sulu.rlp')->willReturn($property);
        $property->setValue('path/to/doc/pub')->shouldBeCalled();

        $contentType = $this->prophesize(ContentTypeInterface::class);
        $this->contentTypeManager->get('content-type')->willReturn($contentType);

        $translatedProperty = $this->prophesize(PropertyInterface::class);
        $this->nodeHelper->getTranslatedProperty($property, $languageCode)->willReturn($translatedProperty);
        $contentType->write($childNode, $translatedProperty, null, $webspaceKey, $languageCode, null)->shouldBeCalled();
        $childDocument->setResourceSegment('path/to/doc/pub')->shouldBeCalled();
        $childDocument->getResourceSegment()->willReturn('path/to/doc/pub');

        $this->mapper->loadByContent($childNode, $webspaceKey, $languageCode, null)->willReturn('path/to/olddoc/pub');
        $this->cleaner->validate('path/to/doc/pub')->willReturn(true);
        $this->mapper->unique('path/to/doc/pub', $webspaceKey, $languageCode)->willReturn(true);
        $this->mapper->save($childDocument)->shouldBeCalled();

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testSaveWithUnpublishedChild()
    {
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $this->nodeHelper->getTranslatedPropertyName('template', $languageCode)->willReturn('template-prop');
        $structure = $this->prophesize(StructureInterface::class);
        $this->structureManager->getStructure('default')->willReturn($structure);

        $session = $this->prophesize(SessionInterface::class);
        $session->save()->shouldBeCalledTimes(2);

        $childDocument = $this->prophesize(PageDocument::class);
        $childDocument->getResourceSegment()->willReturn('path/to/olddoc/pub');
        $childDocument->getChildren()->willReturn([]);
        $childDocument->getPublished()->willReturn(false);

        $childNode = $this->prophesize(NodeInterface::class);
        $childNode->getSession()->willReturn($session);
        $childNode->getPropertyValue('template-prop')->willReturn('default');

        $this->documentInspector->getNode($childDocument)->willReturn($childNode);
        $this->documentInspector->getWebspace($childDocument)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($childDocument)->willReturn($languageCode);
        $this->documentInspector->getUuid($childDocument)->willReturn('published-child-uuid-uuid');

        $document = $this->prophesize(PageDocument::class);
        $document->getResourceSegment()->willReturn('path/to/doc');
        $document->getChildren()->willReturn([$childDocument]);
        $document->getPublished()->willReturn(true);

        $node = $this->prophesize(NodeInterface::class);
        $node->getSession()->willReturn($session);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn($webspaceKey);
        $this->documentInspector->getLocale($document)->willReturn($languageCode);
        $this->documentInspector->getUuid($document)->willReturn('uuid-uuid-uuid-uuid');

        $this->mapper->loadByContent($node, $webspaceKey, $languageCode, null)->willReturn('old/path');
        $this->cleaner->validate('path/to/doc')->willReturn(true);
        $this->mapper->unique('path/to/doc', $webspaceKey, $languageCode)->willReturn(true);
        $this->mapper->save($document)->shouldBeCalled();

        // adapt published child
        $this->documentManager->find('uuid-uuid-uuid-uuid', $languageCode, ['load_ghost_content' => false])->willReturn($document);
        $this->documentInspector->getUuid($document)->willReturn('uuid-uuid-uuid-uuid');
        $this->mapper->loadByContentUuid('uuid-uuid-uuid-uuid', $webspaceKey, $languageCode, null)->willReturn('path/to/doc');
        $this->cleaner->cleanup('path/to/doc/pub', $languageCode)->willReturn('path/to/doc/pub');
        $this->mapper->getUniquePath('path/to/doc/pub', $webspaceKey, $languageCode, null)->willReturn('path/to/doc/pub');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getContentTypeName()->willReturn('content-type');
        $structure->getPropertyByTagName('sulu.rlp')->willReturn($property);
        $property->setValue('path/to/doc/pub')->shouldBeCalled();

        $contentType = $this->prophesize(ContentTypeInterface::class);
        $this->contentTypeManager->get('content-type')->willReturn($contentType);

        $translatedProperty = $this->prophesize(PropertyInterface::class);
        $this->nodeHelper->getTranslatedProperty($property, $languageCode)->willReturn($translatedProperty);
        $contentType->write($childNode, $translatedProperty, null, $webspaceKey, $languageCode, null)->shouldBeCalled();
        $childDocument->setResourceSegment('path/to/doc/pub')->shouldBeCalled();
        $childDocument->getResourceSegment()->willReturn('path/to/doc/pub');

        $this->treeStrategy->save($document->reveal(), null);
    }

    public function testLoadByContent()
    {
        $document = $this->prophesize(PageDocument::class);
        $node = $this->prophesize(NodeInterface::class);

        $this->documentInspector->getNode($document)->willReturn($node);
        $this->documentInspector->getWebspace($document)->willReturn('sulu_io');
        $this->documentInspector->getLocale($document)->willReturn('en');

        $this->mapper->loadByContent($node, 'sulu_io', 'en', null)->willReturn('path/to/document');

        $result = $this->treeStrategy->loadByContent($document->reveal());
        $this->assertEquals('path/to/document', $result);
    }

    public function testLoadByContentUuid()
    {
        $uuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, null)->willReturn('path/to/document');

        $result = $this->treeStrategy->loadByContentUuid($uuid, $webspaceKey, $languageCode);
        $this->assertEquals('path/to/document', $result);
    }

    public function testLoadByContentUuidWithSegmentKey()
    {
        $uuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = 'segment';

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey)->willReturn('path/to/document');

        $result = $this->treeStrategy->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
        $this->assertEquals('path/to/document', $result);
    }

    public function testLoadHistoryByContentUuid()
    {
        $uuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $resourceLocator = $this->prophesize(ResourceLocatorInformation::class);
        $resourceLocator->getResourceLocator()->willReturn('old/path');
        $this->mapper->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, null)->willReturn([$resourceLocator]);

        $result = $this->treeStrategy->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode);
        $this->assertEquals('old/path', $result[0]->getResourceLocator());
    }

    public function testLoadHistoryByContentUuidWithSegmentKey()
    {
        $uuid = 'uuid-uuid-uuid-uuid';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = 'segment';

        $resourceLocator = $this->prophesize(ResourceLocatorInformation::class);
        $resourceLocator->getResourceLocator()->willReturn('old/path');
        $this->mapper->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey)->willReturn([$resourceLocator]);

        $result = $this->treeStrategy->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
        $this->assertEquals('old/path', $result[0]->getResourceLocator());
    }

    public function testLoadByResourceLocator()
    {
        $resourceLocator = 'path/to/document';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $this->mapper->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, null)->willReturn('uuid');

        $result = $this->treeStrategy->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode);
        $this->assertEquals('uuid', $result);
    }

    public function testLoadByResourceLocatorWithSegmentKey()
    {
        $resourceLocator = 'path/to/document';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = 'segment';

        $this->mapper->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey)->willReturn('uuid');

        $result = $this->treeStrategy->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey);
        $this->assertEquals('uuid', $result);
    }

    public function testIsValid()
    {
        $path = 'som/valid/path';
        $this->cleaner->validate($path)->willReturn(true);

        $this->assertTrue($this->treeStrategy->isValid($path, 'default', 'de'));
    }

    public function testIsValidSlash()
    {
        $this->assertFalse($this->treeStrategy->isValid('/', 'default', 'de'));
    }

    public function testDeleteByPath()
    {
        $path = 'path/to/document';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $this->mapper->deleteByPath($path, $webspaceKey, $languageCode, null)->shouldBeCalled();
        $this->treeStrategy->deleteByPath($path, $webspaceKey, $languageCode);
    }

    public function testDeleteByPathWithSegment()
    {
        $path = 'path/to/document';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = 'segment';

        $this->mapper->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey)->shouldBeCalled();
        $this->treeStrategy->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }
}
