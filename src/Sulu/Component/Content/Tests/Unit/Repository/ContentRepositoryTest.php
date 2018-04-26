<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Repository;

use Jackalope\Query\Row;
use PHPCR\Query\QOM\ChildNodeInterface;
use PHPCR\Query\QOM\ColumnInterface;
use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\PropertyValueInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SelectorInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\ContentRepository;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ContentRepositoryTest extends TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var QueryInterface
     */
    private $query;

    public function setUp()
    {
        $this->session = $this->prophesize(SessionInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->localizationFinder = $this->prophesize(LocalizationFinderInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);

        $webspace = $this->prophesize(Webspace::class);
        $this->webspaceManager->findWebspaceByKey(Argument::any())->willReturn($webspace->reveal());
        $webspace->getAllLocalizations()->willReturn(
            [
                new Localization('de', 'at'),
            ]
        );

        $this->sessionManager->getSession()->willReturn($this->session->reveal());

        $workspace = $this->prophesize(WorkspaceInterface::class);
        $this->session->getWorkspace()->willReturn($workspace->reveal());

        $queryManager = $this->prophesize(QueryManagerInterface::class);
        $workspace->getQueryManager()->willReturn($queryManager);

        $qomFactory = $this->prophesize(QueryObjectModelFactoryInterface::class);
        $queryManager->getQOMFactory()->willReturn($qomFactory);

        $this->contentRepository = new ContentRepository(
            $this->sessionManager->reveal(),
            $this->propertyEncoder->reveal(),
            $this->webspaceManager->reveal(),
            $this->localizationFinder->reveal(),
            $this->structureManager->reveal(),
            $this->nodeHelper->reveal()
        );

        $qomFactory->selector(Argument::cetera())->willReturn($this->prophesize(SelectorInterface::class)->reveal());
        $qomFactory->column(Argument::cetera())->willReturn($this->prophesize(ColumnInterface::class)->reveal());
        $qomFactory->propertyValue(Argument::cetera())->willReturn(
            $this->prophesize(PropertyValueInterface::class)->reveal()
        );
        $qomFactory->ascending(Argument::cetera())->willReturn($this->prophesize(OrderingInterface::class)->reveal());
        $qomFactory->literal(Argument::cetera())->willReturn(
            $this->prophesize(StaticOperandInterface::class)->reveal()
        );
        $qomFactory->comparison(Argument::cetera())->willReturn(
            $this->prophesize(ComparisonInterface::class)->reveal()
        );
        $qomFactory->childNode(Argument::cetera())->willReturn($this->prophesize(ChildNodeInterface::class)->reveal());

        $structure = $this->prophesize(StructureInterface::class);
        $this->structureManager->getStructures('page')->willReturn([$structure->reveal()]);
        $this->structureManager->getStructure('test')->willReturn($structure->reveal());

        $this->query = $this->prophesize(QueryInterface::class);
        $this->query->setLimit(Argument::any())->willReturn(null);
        $qomFactory->createQuery(Argument::cetera())->willReturn($this->query->reveal());
    }

    public function testFindWithBrokenTemplate()
    {
        $mapping = MappingBuilder::create()->setResolveUrl(true)->getMapping();

        $queryResult = $this->prophesize(QueryResultInterface::class);
        $this->query->execute()->willReturn($queryResult->reveal());

        $row = $this->prophesize(Row::class);
        $rowIterator = new \ArrayIterator([$row->reveal()]);
        $queryResult->getRows()->willReturn($rowIterator);

        $row->getPath()->willReturn('/cmf/sulu_io/contents');
        $this->nodeHelper->extractWebspaceFromPath('/cmf/sulu_io/contents')->willReturn('sulu_io');

        $row->getValues()->willReturn(
            [
                'node.deTemplate' => 'default',
                'shadowOn' => false,
                'node.deShadow_on' => false,
            ]
        );
        $row->getValue('node.deShadow_on')->willReturn(false);
        $row->getValue('shadowOn')->willReturn(false);
        $row->getValue('nodeType')->willReturn(RedirectType::NONE);
        $row->getValue('uuid')->willReturn('123-123-123');
        $row->getValue('state')->willReturn(WorkflowStage::PUBLISHED);
        $row->getValue('nodeType')->willReturn(1);
        $row->getValue('deTemplate')->willReturn('default');
        $row->getValue('deDeState')->willReturn(WorkflowStage::PUBLISHED);
        $row->getValue('de_atDe_atState')->willReturn(WorkflowStage::TEST);

        $this->sessionManager->getContentPath('sulu_io')->willReturn('/cmf/sulu_io/contents');

        $this->structureManager->getStructure('default')->willReturn(null);

        $result = $this->contentRepository->find('123-123-123', 'de', 'sulu_io', $mapping);

        $this->assertTrue($result->isBrokenTemplate());
    }
}
