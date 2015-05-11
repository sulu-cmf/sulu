<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit;

use Sulu\Bundle\SnippetBundle\Twig\SnippetTwigExtension;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\Snippet;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;

class SnippetTwigExtensionTest extends SuluTestCase
{
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

    protected function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->requestAnalyzer = $this->getContainer()->get('sulu_core.webspace.request_analyzer');
        $this->structureResolver = $this->getContainer()->get('sulu_website.resolver.structure');

        $this->requestAnalyzer->setWebspaceKey('sulu_io');
        $this->requestAnalyzer->setLocalizationCode('en');

        $this->initPhpcr();

        $this->extension = new SnippetTwigExtension(
            $this->contentMapper,
            $this->requestAnalyzer,
            $this->structureResolver
        );
    }

    public function loadProvider()
    {
        $data = array('title' => 'test-title', 'description' => 'test-description');
        $data = $this->getContainer()->get('sulu.content.mapper')->save(
            $data,
            'car',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED,
            null,
            null,
            Structure::TYPE_SNIPPET
        );

        return array(array($data));
    }

    public function testLoadSnippet()
    {
        $data = array('title' => 'test-title', 'description' => 'test-description');
        $data = $this->getContainer()->get('sulu.content.mapper')->save(
            $data,
            'car',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED,
            null,
            null,
            Structure::TYPE_SNIPPET
        );

        $snippet = $this->extension->loadSnippet($data->getUuid());

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(2, $snippet['view']);
        $this->assertCount(2, $snippet['content']);

        $this->assertEquals('test-title', $snippet['content']['title']);
        $this->assertEquals('test-description', $snippet['content']['description']);

        $this->assertEquals(array(), $snippet['view']['title']);
        $this->assertEquals(array(), $snippet['view']['description']);
    }

    public function testLoadSnippetLocale()
    {
        $dataDe = array('title' => 'de-test-title', 'description' => 'de-test-description');
        $dataDe = $this->contentMapper->save(
            $dataDe,
            'car',
            'default',
            'de',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED,
            null,
            null,
            Structure::TYPE_SNIPPET
        );
        $dataEn = array('title' => 'en-test-title', 'description' => 'en-test-description');
        $dataEn = $this->contentMapper->save(
            $dataEn,
            'car',
            'default',
            'en',
            1,
            true,
            $dataDe->getUuid(),
            null,
            Structure::STATE_PUBLISHED,
            null,
            null,
            Structure::TYPE_SNIPPET
        );

        $snippet = $this->extension->loadSnippet($dataDe->getUuid(), 'en');

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(2, $snippet['view']);
        $this->assertCount(2, $snippet['content']);

        $this->assertEquals('en-test-title', $snippet['content']['title']);
        $this->assertEquals('en-test-description', $snippet['content']['description']);

        $this->assertEquals(array(), $snippet['view']['title']);
        $this->assertEquals(array(), $snippet['view']['description']);

        $snippet = $this->extension->loadSnippet($dataDe->getUuid(), 'de');

        $this->assertArrayHasKey('content', $snippet);
        $this->assertArrayHasKey('view', $snippet);
        $this->assertArrayHasKey('uuid', $snippet);
        $this->assertArrayHasKey('created', $snippet);
        $this->assertArrayHasKey('creator', $snippet);
        $this->assertArrayHasKey('changed', $snippet);
        $this->assertArrayHasKey('changer', $snippet);

        $this->assertCount(2, $snippet['view']);
        $this->assertCount(2, $snippet['content']);

        $this->assertEquals('de-test-title', $snippet['content']['title']);
        $this->assertEquals('de-test-description', $snippet['content']['description']);

        $this->assertEquals(array(), $snippet['view']['title']);
        $this->assertEquals(array(), $snippet['view']['description']);
    }
}
