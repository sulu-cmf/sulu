<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedLastModifiedBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

class StructureResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ObjectProphecy<ContentTypeManagerInterface>
     */
    private $contentTypeManager;

    /**
     * @var ObjectProphecy<ContentTypeInterface>
     */
    private $contentType;

    /**
     * @var ObjectProphecy<ExtensionManagerInterface>
     */
    private $extensionManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);

        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->extensionManager->reveal()
        );
    }

    public function testResolve(): void
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);
        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize(ExtensionInterface::class);
        $excerptExtension->getContentData(['test1' => 'test1'])->willReturn(['test1' => 'test1']);
        $this->extensionManager->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('property-1');
        $property1->getContentTypeName()->willReturn('content_type');

        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('property-2');
        $property2->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize(PageBridge::class);
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(new ExtensionContainer(['excerpt' => ['test1' => 'test1']]));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn([$property1->reveal(), $property2->reveal()]);
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getPath()->willReturn('test-path');
        $structure->getUrls()->willReturn(['en' => '/description', 'de' => '/beschreibung', 'es' => null]);
        $structure->getShadowBaseLanguage()->willReturn('en');
        $structure->getWebspaceKey()->willReturn('test');

        $authored = new \DateTime();
        $lastModified = new \DateTime();
        $lastModified->modify('+ 1 day');

        $document = $this->prophesize()->willImplement(LocalizedAuthorBehavior::class);
        $document->willImplement(ExtensionBehavior::class);
        $document->willImplement(LocalizedLastModifiedBehavior::class);
        $structure->getDocument()->willReturn($document->reveal());
        $document->getAuthored()->willReturn($authored);
        $document->getAuthor()->willReturn(1);
        $document->getLastModifiedEnabled()->willReturn(true);
        $document->getlastModified()->willReturn($lastModified);

        $expected = [
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
            'id' => 'some-uuid',
            'uuid' => 'some-uuid',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => ['en' => '/description', 'de' => '/beschreibung', 'es' => null],
            'path' => 'test-path',
            'shadowBaseLocale' => 'en',
            'lastModified' => $lastModified,
            'authored' => $authored,
            'author' => 1,
            'webspaceKey' => 'test',
        ];

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal()));
    }

    public function testResolveWithoutPathParameter(): void
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);

        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize(ExtensionInterface::class);
        $excerptExtension->getContentData(['test1' => 'test1'])->willReturn(['test1' => 'test1']);
        $this->extensionManager->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property');
        $property->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize(PageBridge::class);
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(new ExtensionContainer(['excerpt' => ['test1' => 'test1']]));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn([$property->reveal()]);
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getUrls()->willReturn(['en' => '/description', 'de' => '/beschreibung', 'es' => null]);
        $structure->getShadowBaseLanguage()->willReturn('en');
        $structure->getWebspaceKey()->willReturn('test');

        $authored = new \DateTime();
        $lastModified = new \DateTime();
        $lastModified->modify('+ 1 day');

        $document = $this->prophesize()->willImplement(LocalizedAuthorBehavior::class);
        $document->willImplement(ExtensionBehavior::class);
        $document->willImplement(LocalizedLastModifiedBehavior::class);
        $structure->getDocument()->willReturn($document->reveal());
        $document->getAuthored()->willReturn($authored);
        $document->getAuthor()->willReturn(1);
        $document->getLastModifiedEnabled()->willReturn(true);
        $document->getLastModified()->willReturn($lastModified);

        $expected = [
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
            'id' => 'some-uuid',
            'uuid' => 'some-uuid',
            'view' => [
                'property' => 'view',
            ],
            'content' => [
                'property' => 'content',
            ],
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => ['en' => '/description', 'de' => '/beschreibung', 'es' => null],
            'shadowBaseLocale' => 'en',
            'lastModified' => $lastModified,
            'authored' => $authored,
            'author' => 1,
            'webspaceKey' => 'test',
        ];

        $structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->extensionManager->reveal(),
            ['path' => false]
        );

        $this->assertEquals($expected, $structureResolver->resolve($structure->reveal()));
    }

    public function testResolveWithoutExtensions(): void
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);

        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize(ExtensionInterface::class);
        $excerptExtension->getContentData(['test1' => 'test1'])->willReturn(['test1' => 'test1']);
        $this->extensionManager->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property');
        $property->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize(PageBridge::class);
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(new ExtensionContainer(['excerpt' => ['test1' => 'test1']]));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn([$property->reveal()]);
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getPath()->willReturn('test-path');
        $structure->getUrls()->willReturn(['en' => '/description', 'de' => '/beschreibung', 'es' => null]);
        $structure->getShadowBaseLanguage()->willReturn('en');
        $structure->getWebspaceKey()->willReturn('test');

        $authored = new \DateTime();
        $lastModified = new \DateTime();
        $lastModified->modify('+ 1 day');

        $document = $this->prophesize()->willImplement(LocalizedAuthorBehavior::class);
        $document->willImplement(ExtensionBehavior::class);
        $document->willImplement(LocalizedLastModifiedBehavior::class);
        $structure->getDocument()->willReturn($document->reveal());
        $document->getAuthored()->willReturn($authored);
        $document->getAuthor()->willReturn(1);
        $document->getLastModifiedEnabled()->willReturn(true);
        $document->getLastModified()->willReturn($lastModified);

        $expected = [
            'id' => 'some-uuid',
            'uuid' => 'some-uuid',
            'view' => [
                'property' => 'view',
            ],
            'content' => [
                'property' => 'content',
            ],
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => ['en' => '/description', 'de' => '/beschreibung', 'es' => null],
            'path' => 'test-path',
            'shadowBaseLocale' => 'en',
            'lastModified' => $lastModified,
            'authored' => $authored,
            'author' => 1,
            'webspaceKey' => 'test',
        ];

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal(), false));
    }

    public function testResolveWithIncludedProperties(): void
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);
        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize(ExtensionInterface::class);
        $excerptExtension->getContentData(['test1' => 'test1'])->willReturn(['test1' => 'test1']);
        $this->extensionManager->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('property-1');
        $property1->getContentTypeName()->willReturn('content_type');

        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('property-2');
        $property2->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize(PageBridge::class);
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(new ExtensionContainer(['excerpt' => ['test1' => 'test1']]));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn([$property1->reveal(), $property2->reveal()]);
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getPath()->willReturn('test-path');
        $structure->getUrls()->willReturn(['en' => '/description', 'de' => '/beschreibung', 'es' => null]);
        $structure->getShadowBaseLanguage()->willReturn('en');
        $structure->getWebspaceKey()->willReturn('test');

        $authored = new \DateTime();

        $document = $this->prophesize()->willImplement(LocalizedAuthorBehavior::class);
        $document->willImplement(ExtensionBehavior::class);
        $document->willImplement(LocalizedLastModifiedBehavior::class);
        $structure->getDocument()->willReturn($document->reveal());
        $document->getAuthored()->willReturn($authored);
        $document->getAuthor()->willReturn(1);
        $document->getLastModifiedEnabled()->willReturn(false);
        $document->getLastModified()->willReturn(null);

        $expected = [
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
            'id' => 'some-uuid',
            'uuid' => 'some-uuid',
            'view' => [
                'property-2' => 'view',
            ],
            'content' => [
                'property-2' => 'content',
            ],
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => ['en' => '/description', 'de' => '/beschreibung', 'es' => null],
            'path' => 'test-path',
            'shadowBaseLocale' => 'en',
            'lastModified' => null,
            'authored' => $authored,
            'author' => 1,
            'webspaceKey' => 'test',
        ];

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal(), true, ['property-2']));
    }
}
