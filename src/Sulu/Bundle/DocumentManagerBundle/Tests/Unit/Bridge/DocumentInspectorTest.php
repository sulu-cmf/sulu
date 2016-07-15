<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Bridge;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class DocumentInspectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var PathSegmentRegistry
     */
    private $pathSegmentRegistry;

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var Metadata\
     */
    private $metadata;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pathSegmentRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->document = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentInspector = new DocumentInspector(
            $this->documentRegistry->reveal(),
            $this->pathSegmentRegistry->reveal(),
            $this->namespaceRegistry->reveal(),
            $this->proxyFactory->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureMetadataFactory->reveal(),
            $this->propertyEncoder->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    /**
     * It should return the webspace for the given document.
     *
     * @dataProvider provideGetWebspace
     */
    public function testGetWebspace($path, $expectedWebspace)
    {
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->pathSegmentRegistry->getPathSegment('base')->willReturn('cmf');
        $this->node->getPath()->willReturn($path);

        $webspace = $this->documentInspector->getWebspace($this->document);
        $this->assertEquals($expectedWebspace, $webspace);
    }

    public function provideGetWebspace()
    {
        return [
            ['/cmf/sulu_io/content/articles/article-one', 'sulu_io'],
            ['/cmfcontent/articles/article-one', null],
            ['/cmf/webspace_five', null],
            ['/cmf/webspace_five/foo/bar/dar/ding', 'webspace_five'],
            ['', null],
            ['asdasd', null],
        ];
    }

    /**
     * It should return the Structure for a document implementing StructureBehavior.
     */
    public function testGetStructure()
    {
        $structure = new \stdClass();
        $document = $this->prophesize(StructureBehavior::class);
        $document->getStructureType()->willReturn('foo');

        $this->metadataFactory->getMetadataForClass(get_class($document->reveal()))->willReturn($this->metadata->reveal());
        $this->metadata->getAlias()->willReturn('page');
        $this->structureMetadataFactory->getStructureMetadata('page', 'foo')->willReturn($structure);
        $result = $this->documentInspector->getStructureMetadata($document->reveal());
        $this->assertSame($structure, $result);
    }

    /**
     * It should return the available localizations for the document.
     */
    public function testGetLocales()
    {
        $propertyNames = [
            'foo:aa-template',
            'foo:bb-template',
            'doo:dd-template',
            'foo:cc-barbar',
            'foo:ee-template',
        ];

        $expectedLocales = ['aa', 'bb', 'cc', 'ee'];

        $properties = [];
        foreach ($propertyNames as $propertyName) {
            $property = $this->prophesize(PropertyInterface::class);
            $property->getName()->willReturn($propertyName);
            $properties[] = $property;
        }

        $document = $this->prophesize(StructureBehavior::class);
        $this->documentRegistry->getNodeForDocument($document)->willReturn($this->node->reveal());
        $this->namespaceRegistry->getPrefix('system_localized')->willReturn('foo');
        $this->node->getProperties()->willReturn($properties);

        $locales = $this->documentInspector->getLocales($document->reveal());

        $this->assertEquals(
            $expectedLocales,
            $locales
        );
    }

    /**
     * It should return the locale for a document.
     */
    public function testGetLocale()
    {
        $document = new \stdClass();
        $this->documentRegistry->hasDocument($document)->willReturn(true);
        $this->documentRegistry->getLocaleForDocument($document)->willReturn('fr');
        $locale = $this->documentInspector->getLocale($document);

        $this->assertEquals('fr', $locale);
    }

    /**
     * It should return the locale for a document.
     */
    public function testGetLocaleNotRegistered()
    {
        $document = new \stdClass();
        $this->documentRegistry->hasDocument($document)->willReturn(false);
        $locale = $this->documentInspector->getLocale($document);

        $this->assertNull($locale);
    }

    public function testGetConcreteLocales()
    {
        $document = $this->prophesize(ShadowLocaleBehavior::class);
        $this->namespaceRegistry->getPrefix('system_localized')->willReturn('i18n');

        $germanShadowOnProperty = $this->prophesize(PropertyInterface::class);
        $germanShadowOnProperty->getName()->willReturn('i18n:de-shadow-on');
        $englishShadowOnProperty = $this->prophesize(PropertyInterface::class);
        $englishShadowOnProperty->getName()->willReturn('i18n:en-shadow-on');

        $this->propertyEncoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD, 'de')
            ->willReturn('i18n:de-shadow-on');
        $this->propertyEncoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD, 'en')
            ->willReturn('i18n:en-shadow-on');
        $this->propertyEncoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD, 'de')
            ->willReturn('i18n:de-shadow-base');
        $this->propertyEncoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD, 'en')
            ->willReturn('i18n:en-shadow-base');

        $this->node->getPropertyValueWithDefault('i18n:de-shadow-on', false)->willReturn(false);
        $this->node->getPropertyValueWithDefault('i18n:en-shadow-on', false)->willReturn(true);
        $this->node->getPropertyValue('i18n:en-shadow-base')->willReturn('de');

        $this->documentRegistry->getNodeForDocument($document->reveal())->willReturn($this->node->reveal());
        $this->node->getProperties()->willReturn([$englishShadowOnProperty->reveal(), $germanShadowOnProperty->reveal()]);

        $this->assertEquals('["de"]', json_encode($this->documentInspector->getConcreteLocales($document->reveal())));
    }

    /**
     * It should return the webspace name for a given node.
     *
     * @dataProvider provideWebspace
     */
    public function testWebspace($path, $expectedWebspace)
    {
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn($path);
        $this->pathSegmentRegistry->getPathSegment('base')->willReturn('cmf');
        $webspace = $this->documentInspector->getWebspace($this->document);

        $this->assertEquals($expectedWebspace, $webspace);
    }

    public function provideWebspace()
    {
        return [
            [
                '/cmf/sulu.io/contents',
                'sulu.io',
            ],
            [
                '/cmf/foobar/bar',
                'foobar',
            ],
            [
                '/cmf/foo-bar/bar',
                'foo-bar',
            ],
            [
                '/cmf/foo_bar/bar',
                'foo_bar',
            ],
            [
                '/foo/foo',
                null,
            ],
            [
                '/cmf/hello',
                null,
            ],
        ];
    }
}
