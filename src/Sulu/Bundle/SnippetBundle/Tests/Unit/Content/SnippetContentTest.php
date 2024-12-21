<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

class SnippetContentTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DefaultSnippetManagerInterface>
     */
    private $defaultSnippetManager;

    /**
     * @var ObjectProphecy<SnippetResolverInterface>
     */
    private $snippetResolver;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $snippetAreaReferenceStore;

    /**
     * @var SnippetContent
     */
    private $contentType;

    protected function setUp(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->snippetAreaReferenceStore = new ReferenceStore();

        $this->contentType = new SnippetContent(
            $this->defaultSnippetManager->reveal(),
            $this->snippetResolver->reveal(),
            $this->referenceStore->reveal(),
            false,
            $this->snippetAreaReferenceStore,
        );
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $result = $this->contentType->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1']], $result);
    }

    public function testGetContentDataWithExtensions(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn(['loadExcerpt' => new PropertyParameter('loadExcerpt', true)]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', true)
            ->willReturn(
                [
                    [
                        'content' => ['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]],
                        'view' => ['title' => 'test-2', 'template' => 'default'],
                    ],
                ]
            );

        $result = $this->contentType->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]]], $result);
    }

    public function testGetViewData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $result = $this->contentType->getViewData($property->reveal());

        $this->assertEquals([['title' => 'test-2', 'template' => 'default']], $result);
    }

    public function testPreResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->contentType->preResolve($property->reveal());

        $this->referenceStore->add('123-123-123')->shouldBeCalled();
    }

    public function testSnippetContentWithNullSnippetAreaReferenceStore(): void
    {
        $this->contentType = new SnippetContent(
            $this->defaultSnippetManager->reveal(),
            $this->snippetResolver->reveal(),
            $this->referenceStore->reveal(),
            false,
            null
        );

        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );
        $this->referenceStore->add('123-123-123')->shouldNotBeCalled();

        $result = $this->contentType->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1']], $result);
    }

    public function testGetReferencesWithNullProperty(): void
    {
        $property = new Property(
            'snippet',
            new Metadata([]),
            'snippet_selection',
        );
        $property->setValue(null);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(Argument::cetera())->shouldNotHaveBeenCalled();

        $this->contentType->getReferences($property, $referenceCollector->reveal());
    }

    public function testGetReferences(): void
    {
        $property = new Property(
            'snippets',
            new Metadata([]),
            'snippet_selection',
        );
        $property->setValue(['123-123-123', '321-321-321']);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(
            'snippets',
            '123-123-123',
            'snippets'
        )->shouldBeCalled();
        $referenceCollector->addReference(
            'snippets',
            '321-321-321',
            'snippets'
        )->shouldBeCalled();

        $this->contentType->getReferences($property, $referenceCollector->reveal());
    }
}
