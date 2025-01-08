<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\FormMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Exception\InvalidRootTagException;
use Sulu\Bundle\AdminBundle\Exception\PropertyMetadataMapperNotFoundException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\LocalizedFormMetadataCollection;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormXmlLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var FormXmlLoader
     */
    private $loader;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $propertiesXmlParser = new PropertiesXmlParser(
            $this->translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
        $schemaXmlParser = new SchemaXmlParser();
        $locales = ['de', 'en'];

        $propertyMetadataMapperRegistry = $this->prophesize(PropertyMetadataMapperRegistry::class);
        $propertyMetadataMapperRegistry->has(Argument::cetera())->willReturn(false);
        $propertyMetadataMapperRegistry->get(Argument::cetera())->will(function($arguments) {
            throw new PropertyMetadataMapperNotFoundException($arguments[0]);
        });

        $formMetadataMapper = new FormMetadataMapper($propertyMetadataMapperRegistry->reveal());
        $this->loader = new FormXmlLoader($propertiesXmlParser, $schemaXmlParser, $locales, $formMetadataMapper);
    }

    public function testLoadForm(): void
    {
        /** @var LocalizedFormMetadataCollection */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form.xml');

        $this->assertInstanceOf(LocalizedFormMetadataCollection::class, $formMetadataCollection);

        $this->assertCount(2, $formMetadataCollection->getItems());

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertCount(1, $formMetadata->getTags());
        $this->assertCount(1, $formMetadata->getTagsByName('test'));
        $this->assertEquals('test', $formMetadata->getTagsByName('test')[0]->getName());
        $this->assertEquals(['value' => 'test-value'], $formMetadata->getTagsByName('test')[0]->getAttributes());

        $this->assertEquals('form', $formMetadata->getKey());
        $this->assertCount(4, $formMetadata->getItems());

        $this->assertEquals('formOfAddress', $formMetadata->getItems()['formOfAddress']->getName());
        $this->assertEquals(
            'default_value',
            $formMetadata->getItems()['formOfAddress']->getOptions()['default_value']->getName()
        );
        $this->assertSame(0, $formMetadata->getItems()['formOfAddress']->getOptions()['default_value']->getValue());
        $this->assertSame(
            0,
            $formMetadata->getItems()['formOfAddress']->getOptions()['values']->getValue()[0]->getName()
        );
        $this->assertSame(
            1,
            $formMetadata->getItems()['formOfAddress']->getOptions()['values']->getValue()[1]->getName()
        );
        $this->assertEquals('firstName', $formMetadata->getItems()['firstName']->getName());
        $this->assertEquals('lastName', $formMetadata->getItems()['lastName']->getName());
        $this->assertEquals('salutation', $formMetadata->getItems()['salutation']->getName());

        $schemaMetadata = $formMetadata->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadata);
        $this->assertCount(3, $schemaMetadata->toJsonSchema()['required']);
    }

    public function testLoadFormWithLocalization(): void
    {
        $this->translator->trans('sulu_contact.male_form_of_address', [], 'admin', 'en')->willReturn('en_mr');
        $this->translator->trans('sulu_contact.male_form_of_address', [], 'admin', 'de')->willReturn('de_mr');
        $this->translator->trans('sulu_contact.male_form_of_address', [], 'admin', 'fr')->willReturn('fr_mr');
        $this->translator->trans('sulu_contact.male_form_of_address', [], 'admin', 'nl')->willReturn('nl_mr');

        $this->translator->trans('sulu_contact.female_form_of_address', [], 'admin', 'en')->willReturn('en_ms');
        $this->translator->trans('sulu_contact.female_form_of_address', [], 'admin', 'de')->willReturn('de_ms');
        $this->translator->trans('sulu_contact.female_form_of_address', [], 'admin', 'fr')->willReturn('fr_ms');
        $this->translator->trans('sulu_contact.female_form_of_address', [], 'admin', 'nl')->willReturn('nl_ms');

        $this->translator->trans('sulu_contact.form_of_address', [], 'admin', 'en')->willReturn('en_form_of_address');
        $this->translator->trans('sulu_contact.form_of_address', [], 'admin', 'de')->willReturn('de_form_of_address');
        $this->translator->trans('sulu_contact.form_of_address', [], 'admin', 'fr')->willReturn('fr_form_of_address');
        $this->translator->trans('sulu_contact.form_of_address', [], 'admin', 'nl')->willReturn('nl_form_of_address');

        $this->translator->trans('sulu_contact.first_name', [], 'admin', 'en')->willReturn('en_first_name');
        $this->translator->trans('sulu_contact.first_name', [], 'admin', 'de')->willReturn('de_first_name');
        $this->translator->trans('sulu_contact.first_name', [], 'admin', 'fr')->willReturn('fr_first_name');
        $this->translator->trans('sulu_contact.first_name', [], 'admin', 'nl')->willReturn('nl_first_name');

        $this->translator->trans('sulu_contact.last_name', [], 'admin', 'en')->willReturn('en_last_name');
        $this->translator->trans('sulu_contact.last_name', [], 'admin', 'de')->willReturn('de_last_name');
        $this->translator->trans('sulu_contact.last_name', [], 'admin', 'fr')->willReturn('fr_last_name');
        $this->translator->trans('sulu_contact.last_name', [], 'admin', 'nl')->willReturn('nl_last_name');

        $this->translator->trans('sulu_contact.salutation', [], 'admin', 'en')->willReturn('en_salutation');
        $this->translator->trans('sulu_contact.salutation', [], 'admin', 'de')->willReturn('de_salutation');
        $this->translator->trans('sulu_contact.salutation', [], 'admin', 'fr')->willReturn('fr_salutation');
        $this->translator->trans('sulu_contact.salutation', [], 'admin', 'nl')->willReturn('nl_salutation');

        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_localizations.xml');

        $formMetadataEn = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadataEn);
        $this->assertCount(4, $formMetadataEn->getItems());
        $this->assertEquals('en_form_of_address', $formMetadataEn->getItems()['formOfAddress']->getLabel());
        $this->assertEquals('en_first_name', $formMetadataEn->getItems()['firstName']->getLabel());
        $this->assertEquals('en_last_name', $formMetadataEn->getItems()['lastName']->getLabel());
        $this->assertEquals('en_salutation', $formMetadataEn->getItems()['salutation']->getLabel());
        $this->assertEquals('en_mr', $formMetadataEn->getItems()['formOfAddress']->getOptions()['values']
            ->getValue()[0]->getTitle());
        $this->assertEquals('en_ms', $formMetadataEn->getItems()['formOfAddress']->getOptions()['values']
            ->getValue()[1]->getTitle());

        $formMetadataDe = $formMetadataCollection->get('de');
        $this->assertCount(4, $formMetadataDe->getItems());
        $this->assertEquals('de_form_of_address', $formMetadataDe->getItems()['formOfAddress']->getLabel());
        $this->assertEquals('de_first_name', $formMetadataDe->getItems()['firstName']->getLabel());
        $this->assertEquals('Deutscher Nachname', $formMetadataDe->getItems()['lastName']->getLabel());
        $this->assertEquals('de_salutation', $formMetadataDe->getItems()['salutation']->getLabel());
        $this->assertEquals('de_mr', $formMetadataDe->getItems()['formOfAddress']->getOptions()['values']->getValue()[0]->getTitle());
        $this->assertEquals('de_ms', $formMetadataDe->getItems()['formOfAddress']->getOptions()['values']->getValue()[1]->getTitle());

        $schemaMetadataEn = $formMetadataEn->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadataEn);
        $this->assertCount(3, $schemaMetadataEn->toJsonSchema()['required']);

        $schemaMetadataDe = $formMetadataDe->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadataDe);
        $this->assertCount(3, $schemaMetadataDe->toJsonSchema()['required']);
    }

    public function testLoadFormWithEvaluations(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_evaluations.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(6, $formMetadata->getItems());

        $this->assertEquals(
            'lastName == \'section_property\'',
            $formMetadata->getItems()['highlight']->getItems()['formOfAddress']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'section_property\'',
            $formMetadata->getItems()['highlight']->getItems()['formOfAddress']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block\'',
            $formMetadata->getItems()['block']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block\'',
            $formMetadata->getItems()['block']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block_property\'',
            $formMetadata->getItems()['block']->getTypes()['test']->getItems()['name']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block_property\'',
            $formMetadata->getItems()['block']->getTypes()['test']->getItems()['name']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'property\'',
            $formMetadata->getItems()['salutation']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'property\'',
            $formMetadata->getItems()['salutation']->getVisibleCondition()
        );

        $this->assertEquals(
            'false',
            $formMetadata->getItems()['title']->getVisibleCondition()
        );

        $this->assertEquals(
            'true',
            $formMetadata->getItems()['title']->getDisabledCondition()
        );
    }

    public function testLoadFormWithSchema(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_schema.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(3, $formMetadata->getItems());

        $this->assertEquals('first', $formMetadata->getItems()['first']->getName());
        $this->assertEquals('second', $formMetadata->getItems()['second']->getName());

        $this->assertEquals(
            [
                'allOf' => [
                    [
                        'required' => [
                            'first',
                            'third',
                        ],
                        'type' => 'object',
                    ],
                    [
                        'anyOf' => [
                            [
                                'properties' => [
                                    'first' => [
                                        'const' => 1,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                            [
                                'properties' => [
                                    'second' => [
                                        'const' => 2,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                        'allOf' => [
                            [
                                'properties' => [
                                    'first' => [
                                        'const' => 1,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
            ],
            $formMetadata->getSchema()->toJsonSchema()
        );
    }

    public function testLoadFormSchemaWithBlocks(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_blocks.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertEquals(
            [
                'properties' => [
                    'blocks' => [
                        'type' => 'array',
                        'items' => [
                            'allOf' => [
                                [
                                    'if' => [
                                        'properties' => [
                                            'type' => [
                                                'const' => 'editor',
                                            ],
                                        ],
                                        'required' => ['type'],
                                        'type' => 'object',
                                    ],
                                    'then' => [
                                        'required' => ['article'],
                                        'type' => 'object',
                                    ],
                                ],
                                [
                                    'if' => [
                                        'properties' => [
                                            'type' => [
                                                'const' => 'editor_image',
                                            ],
                                        ],
                                        'required' => ['type'],
                                        'type' => 'object',
                                    ],
                                    'then' => [
                                        'required' => ['images'],
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            $formMetadata->getSchema()->toJsonSchema()
        );
    }

    public function testLoadFormMetadataWithNestedBlocks(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_nested_blocks.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertEquals(
            [
                'properties' => [
                    'block1' => [
                        'type' => 'array',
                        'items' => [
                            'allOf' => [
                                [
                                    'if' => [
                                        'properties' => [
                                            'type' => [
                                                'const' => 'type11',
                                            ],
                                        ],
                                        'required' => ['type'],
                                        'type' => 'object',
                                    ],
                                    'then' => [
                                        'properties' => [
                                            'block11' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'allOf' => [
                                                        [
                                                            'if' => [
                                                                'properties' => [
                                                                    'type' => [
                                                                        'const' => 'type111',
                                                                    ],
                                                                ],
                                                                'required' => ['type'],
                                                                'type' => 'object',
                                                            ],
                                                            'then' => [
                                                                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                                            ],
                                                        ],
                                                        [
                                                            'if' => [
                                                                'properties' => [
                                                                    'type' => [
                                                                        'const' => 'type112',
                                                                    ],
                                                                ],
                                                                'required' => ['type'],
                                                                'type' => 'object',
                                                            ],
                                                            'then' => [
                                                                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'required' => ['block11'],
                                        'type' => 'object',
                                    ],
                                ],
                                [
                                    'if' => [
                                        'properties' => [
                                            'type' => [
                                                'const' => 'type12',
                                            ],
                                        ],
                                        'required' => ['type'],
                                        'type' => 'object',
                                    ],
                                    'then' => [
                                        'properties' => [
                                            'block12' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'allOf' => [
                                                        [
                                                            'if' => [
                                                                'properties' => [
                                                                    'type' => [
                                                                        'const' => 'type121',
                                                                    ],
                                                                ],
                                                                'required' => ['type'],
                                                                'type' => 'object',
                                                            ],
                                                            'then' => [
                                                                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                                            ],
                                                        ],
                                                        [
                                                            'if' => [
                                                                'properties' => [
                                                                    'type' => [
                                                                        'const' => 'type122',
                                                                    ],
                                                                ],
                                                                'required' => ['type'],
                                                                'type' => 'object',
                                                            ],
                                                            'then' => [
                                                                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'type' => 'object',
            ],
            $formMetadata->getSchema()->toJsonSchema()
        );

        $block1Types = $formMetadata->getItems()['block1']->getTypes();
        $block11 = $block1Types['type11']->getItems()['block11'];
        $block11Types = $block11->getTypes();
        $type111Items = $block11Types['type111']->getItems();
        $type112Items = $block11Types['type112']->getItems();

        $this->assertEquals($block11->getDefaultType(), 'type111');
        $this->assertCount(1, $type111Items);
        $this->assertEquals('headline1', $type111Items['headline1']->getName());
        $this->assertCount(1, $type112Items);
        $this->assertEquals('headline2', $type112Items['headline2']->getName());
    }

    public function testLoadFormWithoutLabel(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_without_label.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);
    }

    public function testLoadFormWithExpressionParam(): void
    {
        /**
         * LocalizedFormMetadataCollection.
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_expression_param.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertEquals(
            'service(\'test\').getId()',
            $formMetadata->getItems()['name']->getOptions()['id']->getValue()
        );

        $this->assertEquals(
            'expression',
            $formMetadata->getItems()['name']->getOptions()['id']->getType()
        );
    }

    public function testLoadFormWithSizedSections(): void
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_sections.xml');

        $formMetadata = $formMetadataCollection->get('en');
        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(2, $formMetadata->getItems());
        $this->assertEquals('logo', $formMetadata->getItems()['logo']->getName());
        $this->assertEquals(4, $formMetadata->getItems()['logo']->getColSpan());
        $this->assertCount(1, $formMetadata->getItems()['logo']->getItems());
        $this->assertEquals('name', $formMetadata->getItems()['name']->getName());
        $this->assertEquals(8, $formMetadata->getItems()['name']->getColSpan());
        $this->assertCount(1, $formMetadata->getItems()['name']->getItems());
    }

    public function testLoadFormInvalidRootTag(): void
    {
        $this->expectException(InvalidRootTagException::class);
        $this->expectExceptionMessageMatches('/"form"/');

        $this->loader->load(
            __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'form_invalid_root_tag.xml'
        );
    }

    public function testLoadFormInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loader->load(
            __DIR__ . \DIRECTORY_SEPARATOR . 'data' . \DIRECTORY_SEPARATOR . 'form_invalid.xml'
        );
    }

    private function getFormDirectory()
    {
        return __DIR__ . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR
            . 'Application' . \DIRECTORY_SEPARATOR . 'config' . \DIRECTORY_SEPARATOR . 'forms' . \DIRECTORY_SEPARATOR;
    }
}
