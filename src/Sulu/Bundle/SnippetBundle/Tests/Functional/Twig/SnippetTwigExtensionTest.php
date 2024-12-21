<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Twig;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Twig\SnippetTwigExtension;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SnippetTwigExtensionTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var SnippetTwigExtension
     */
    private $extension;

    /**
     * @var RequestStack
     */
    private $requestStack;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->requestAnalyzer = $this->getContainer()->get('sulu_core.webspace.request_analyzer');
        $this->structureResolver = $this->getContainer()->get('sulu_website.resolver.structure');

        $webspace = $this->getContainer()->get('sulu_core.webspace.webspace_manager')->findWebspaceByKey('sulu_io');
        $localization = $webspace->getLocalization('en');

        $request = new Request(
            [],
            [],
            [
                '_sulu' => new RequestAttributes(
                    [
                        'webspaceKey' => $webspace->getKey(),
                        'webspace' => $webspace,
                        'locale' => $localization->getLocale(),
                        'localization' => $localization,
                    ]
                ),
            ]
        );

        $this->requestStack = $this->getContainer()->get('request_stack');
        $this->requestStack->push($request);

        $this->initPhpcr();

        $this->extension = new SnippetTwigExtension(
            $this->contentMapper,
            $this->requestAnalyzer,
            $this->structureResolver
        );

        $user = $this->getContainer()->get('test_user_provider')->getUser();
        $this->getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'test'));
    }

    public function testLoadSnippetNotExists(): void
    {
        $snippet = $this->extension->loadSnippet('123-123-123');
        $this->assertNull($snippet);
    }

    public function testLoadSnippet(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('car');
        $snippet->setTitle('test-title');
        $snippet->getStructure()->bind(['description' => 'test-description']);
        $snippet->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($snippet, 'en');
        $this->documentManager->flush();

        $snippet = $this->extension->loadSnippet($snippet->getUuid());

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(3, $snippet['view']);
        $this->assertCount(3, $snippet['content']);

        $this->assertEquals('test-title', $snippet['content']['title']);
        $this->assertEquals('test-description', $snippet['content']['description']);

        $this->assertEquals([], $snippet['view']['title']);
        $this->assertEquals([], $snippet['view']['description']);
    }

    public function testLoadSnippetLocale(): void
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('snippet');
        $document->setTitle('de-test-title');
        $document->getStructure()->bind(['description' => 'de-test-description']);
        $document->setStructureType('car');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $document = $this->documentManager->find($document->getUuid(), 'en');
        $document->setTitle('en-test-title');
        $document->getStructure()->bind(['description' => 'en-test-description']);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->flush();

        $snippet = $this->extension->loadSnippet($document->getUuid(), 'en');

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(3, $snippet['view']);
        $this->assertCount(3, $snippet['content']);

        $this->assertEquals('en-test-title', $snippet['content']['title']);
        $this->assertEquals('en-test-description', $snippet['content']['description']);

        $this->assertEquals([], $snippet['view']['title']);
        $this->assertEquals([], $snippet['view']['description']);

        $snippet = $this->extension->loadSnippet($document->getUuid(), 'de');

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(3, $snippet['view']);
        $this->assertCount(3, $snippet['content']);

        $this->assertEquals('de-test-title', $snippet['content']['title']);
        $this->assertEquals('de-test-description', $snippet['content']['description']);

        $this->assertEquals([], $snippet['view']['title']);
        $this->assertEquals([], $snippet['view']['description']);
    }

    public function testLoadSnippetExcerpt(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('car');
        $snippet->setExtension('excerpt', ['tags' => ['Tag1', 'Tag2'], 'categories' => []]);
        $snippet->setTitle('test-title');
        $snippet->getStructure()->bind(['description' => 'test-description']);
        $snippet->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($snippet, 'en');
        $this->documentManager->flush();

        $snippet = $this->extension->loadSnippet($snippet->getUuid(), null, true);

        $this->assertEquals(['Tag1', 'Tag2'], $snippet['extension']['excerpt']['tags']);
        $this->assertEquals([], $snippet['extension']['excerpt']['categories']);
    }
}
