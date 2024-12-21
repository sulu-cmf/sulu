<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata\Loader;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\InvalidDefaultTypeException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException;
use Sulu\Component\Content\Metadata\Loader\StructureXmlLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Contracts\Translation\TranslatorInterface;

class StructureXmlLoaderTest extends TestCase
{
    use ProphecyTrait;

    private $requiredTagNames = [
        'page' => ['sulu.rlp'],
        'home' => ['sulu.rlp'],
        'snippet' => [],
    ];

    private $requiredPropertyNames = [
        'page' => ['title'],
        'home' => ['title'],
        'snippet' => ['title'],
    ];

    /**
     * @var string[]
     */
    private $locales = [
        'en' => 'en',
        'de' => 'de',
        'fr' => 'fr',
        'nl' => 'nl',
    ];

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var StructureXmlLoader
     */
    private $loader;

    /**
     * @var ObjectProphecy<ContentTypeManagerInterface>
     */
    private $contentTypeManager;

    /**
     * @var ObjectProphecy<CacheLifetimeResolverInterface>
     */
    private $cacheLifetimeResolver;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $propertiesXmlParser = new PropertiesXmlParser(
            $this->translator->reveal(),
            $this->locales
        );
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $schemaXmlParser = new SchemaXmlParser();

        $this->loader = new StructureXmlLoader(
            $this->cacheLifetimeResolver->reveal(),
            $propertiesXmlParser,
            $schemaXmlParser,
            $this->contentTypeManager->reveal(),
            $this->requiredPropertyNames,
            $this->requiredTagNames,
            $this->locales,
            $this->translator->reveal()
        );
    }

    public function testLoadTemplate(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template.xml');

        $this->assertFalse($result->isInternal());
        $this->assertNull($result->getSchema());
    }

    public function testLoadTemplateWithLocalization(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $this->translator->trans('sulu_admin.title', [], 'admin', 'en')->willReturn('en_template_title');
        $this->translator->trans('sulu_admin.title', [], 'admin', 'fr')->willReturn('fr_template_title');
        $this->translator->trans('sulu_admin.title', [], 'admin', 'nl')->willReturn('nl_template_title');

        $this->translator->trans('sulu_admin.name', [], 'admin', 'en')->willReturn('en_property_title');
        $this->translator->trans('sulu_admin.name', [], 'admin', 'de')->willReturn('de_property_title');
        $this->translator->trans('sulu_admin.name', [], 'admin', 'fr')->willReturn('fr_property_title');
        $this->translator->trans('sulu_admin.name', [], 'admin', 'nl')->willReturn('nl_property_title');

        $result = $this->load('template_with_localizations.xml');

        $this->assertEquals('en_template_title', $result->getTitle('en'));
        $this->assertEquals('Template Titel', $result->getTitle('de'));
        $this->assertEquals('fr_template_title', $result->getTitle('fr'));
        $this->assertEquals('nl_template_title', $result->getTitle('nl'));
    }

    public function testLoadTemplateWithSchema(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);
        $this->contentTypeManager->has('checkbox')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_with_schema.xml');

        $this->assertEquals(
            [
                'anyOf' => [
                    [
                        'required' => [
                            'article1',
                        ],
                        'type' => 'object',
                    ],
                    [
                        'required' => [
                            'article2',
                        ],
                        'type' => 'object',
                    ],
                    [
                        'type' => 'object',
                        'properties' => [
                            'checkbox1' => [
                                'const' => true,
                            ],
                            'checkbox2' => [
                                'const' => false,
                            ],
                        ],
                    ],
                ],
            ],
            $result->getSchema()->toJsonSchema()
        );
    }

    public function testLoadInternalTemplate(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('smart_content_selection')->willReturn(true);
        $this->contentTypeManager->has('image_selection')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_load_internal.xml');

        $this->assertTrue($result->isInternal());
    }

    public function testLoadBlockMetaTitles(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_block_types.xml');

        $blockTypes = $result->getProperty('block1')->getComponents();

        $this->assertEquals('Default DE', $blockTypes[0]->getTitle('de'));
        $this->assertEquals('Default EN', $blockTypes[0]->getTitle('en'));
        $this->assertEquals('Test DE', $blockTypes[1]->getTitle('de'));
        $this->assertEquals('Test EN', $blockTypes[1]->getTitle('en'));
        $this->assertEquals('Info Block1 DE', $blockTypes[1]->getDescription('de'));
        $this->assertEquals('Info Block1 EN', $blockTypes[1]->getDescription('en'));
    }

    public function testLoadBlockTypeWithoutMeta(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('text_area')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_block_type_without_meta.xml');

        $this->assertCount(1, $result->getProperty('block1')->getComponents());
    }

    public function testLoadNestedSections(): void
    {
        $result = $this->load('template_with_nested_sections.xml');

        $this->assertEquals(['title', 'test21', 'test221'], \array_keys($result->getProperties()));
    }

    public function testLoadBlockWithSections(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);

        $this->contentTypeManager->getAll()->willReturn(['text_line']);

        $result = $this->load('template_block_with_sections.xml');
        $this->assertEquals(['title', 'url', 'block1'], \array_keys($result->getProperties()));
        $this->assertEquals(['title1.1', 'title1.2'], \array_keys($result->getProperties()['block1']->getComponents()[0]->getChildren()));
    }

    public function testLoadNestedBlocks(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);

        $this->contentTypeManager->getAll()->willReturn(['text_line']);

        $result = $this->load('template_with_nested_blocks.xml');

        $block1Types = $result->getProperty('block1')->getComponents();
        $block11 = $block1Types[0]->getChildren()['block11'];
        $block11Types = $block11->getComponents();
        $type111Children = $block11Types[0]->getChildren();
        $type112Children = $block11Types[1]->getChildren();

        $block12 = $block1Types[1]->getChildren()['block12'];
        $block12Types = $block12->getComponents();
        $type121Children = $block12Types[0]->getChildren();
        $type122Children = $block12Types[1]->getChildren();

        $this->assertEquals('type111', $block11->getDefaultComponentName());
        $this->assertCount(1, $type111Children);
        $this->assertEquals('headline1', $type111Children['headline1']->getName());
        $this->assertCount(1, $type112Children);
        $this->assertEquals('headline2', $type112Children['headline2']->getName());

        $this->assertEquals('type121', $block12->getDefaultComponentName());
        $this->assertCount(1, $type121Children);
        $this->assertEquals('headline1', $type121Children['headline1']->getName());
        $this->assertCount(1, $type122Children);
        $this->assertEquals('headline2', $type122Children['headline2']->getName());
    }

    public function testLoadInvalidIgnore(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('test')->willReturn(false);
        $result = $this->load('template_with_invalid_ignore.xml');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
        $this->assertEquals('title', $properties['title']->getName());
        $this->assertEquals('url', $properties['url']->getName());
    }

    public function testLoadInvalidWithoutIgnore(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->has('test')->willReturn(false);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_invalid_ignore.xml');
    }

    public function testLoadRequiredProperty(): void
    {
        $this->expectException(RequiredPropertyNameNotFoundException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_title.xml');
    }

    public function testLoadRequiredTag(): void
    {
        $this->expectException(RequiredTagNotFoundException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $this->load('template_without_sulu_rlp.xml');
    }

    public function testLoadRequiredPropertyOtherType(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $result = $this->load('template_without_title.xml', 'test');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
    }

    public function testLoadRequiredTagOtherType(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);
        $this->contentTypeManager->getAll()->willReturn(['text_line', 'resource_locator']);
        $result = $this->load('template_without_sulu_rlp.xml', 'test');

        $properties = $result->getProperties();

        $this->assertCount(2, $properties);
    }

    public function testLoadFormWithInvalidBlockDefaultType(): void
    {
        $this->expectException(InvalidDefaultTypeException::class);
        $this->expectExceptionMessage('Property "blocks" has invalid default-type "test". Available types are "editor", "images"');

        $this->load('template_with_invalid_block_default_type.xml');
    }

    public function testLoadBlockWithGlobalBlock(): void
    {
        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->load('template_with_global_blocks.xml');

        $blockTypes = $result->getProperty('blocks')->getComponents();
        $this->assertFalse($blockTypes[0]->hasTag('sulu.global_block'));
        $this->assertTrue($blockTypes[1]->hasTag('sulu.global_block'));
    }

    public function testLoadBlockWithGlobalBlockNoRefOrName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $this->load('template_with_global_blocks_no_ref_or_name.xml');
    }

    public function testLoadBlockWithGlobalBlockRefAndName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->contentTypeManager->has('text_line')->willReturn(true);
        $this->contentTypeManager->has('text_editor')->willReturn(true);
        $this->contentTypeManager->has('block')->willReturn(true);
        $this->contentTypeManager->has('resource_locator')->willReturn(true);

        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $this->load('template_with_global_blocks_ref_and_name.xml');
    }

    private function load($name, $type = null)
    {
        $this->cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, Argument::any())
            ->willReturn(true);

        $result = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Page/' . $name,
            $type
        );

        return $result;
    }

    private function getResourceDirectory()
    {
        return __DIR__ . '/../../../../../../../../tests/Resources';
    }
}
