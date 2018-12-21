<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Metadata\Form;

use Sulu\Bundle\AdminBundle\Metadata\Form\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\Form\Section;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;

class FormMetadataProviderTest extends KernelTestCase
{
    /**
     * @var FormMetadataProvider
     */
    private $formMetadataProvider;

    public function setUp()
    {
        $this->formMetadataProvider = $this->getContainer()->get('sulu_admin_test.form_metadata_provider');
    }

    public function testGetMetadataWithSchema()
    {
        $form = $this->formMetadataProvider->getMetadata('form_with_schema', 'en');
        $schema = $form->getSchema()->toJsonSchema();
        $this->assertCount(1, array_keys($schema));
        $this->assertCount(2, $schema['allOf']);
        $this->assertEquals(['first', 'third'], $schema['allOf'][0]['required']);
    }

    public function testGetMetadataWithEvaluations()
    {
        $form = $this->formMetadataProvider->getMetadata('form_with_evaluations', 'en');

        $this->assertEquals(
            'lastName == \'section_property\'',
            $form->getItems()['highlight']->getItems()['formOfAddress']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'section_property\'',
            $form->getItems()['highlight']->getItems()['formOfAddress']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block\'',
            $form->getItems()['block']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block\'',
            $form->getItems()['block']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block_property\'',
            $form->getItems()['block']->getTypes()['test']->getItems()['name']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block_property\'',
            $form->getItems()['block']->getTypes()['test']->getItems()['name']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'property\'',
            $form->getItems()['salutation']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'property\'',
            $form->getItems()['salutation']->getVisibleCondition()
        );
    }

    public function testGetMetadataWithoutLabel()
    {
        $form = $this->formMetadataProvider->getMetadata('form_without_label', 'en');
        $this->assertNull($form->getItems()['name']->getLabel());
    }

    public function testGetMetadataFromMultipleFiles()
    {
        $form = $this->formMetadataProvider->getMetadata('merged_form', 'en');
        $this->assertContains('field1', array_keys($form->getItems()));
        $this->assertContains('field2', array_keys($form->getItems()));

        $schema = $form->getSchema()->toJsonSchema();
        $this->assertCount(2, $schema['allOf']);
    }

    public function testGetMetadataWithNestedSections()
    {
        $form = $this->formMetadataProvider->getMetadata('form_with_nested_sections', 'en');

        $section1 = $form->getItems()['test1'];
        $section2 = $form->getItems()['test2'];
        $section22 = $section2->getItems()['test22'];

        $this->assertInstanceOf(Section::class, $section1);
        $this->assertInstanceOf(Section::class, $section2);
        $this->assertInstanceOf(Section::class, $section22);

        $this->assertEquals('test11', $section1->getItems()['test11']->getName());
        $this->assertEquals('test21', $section2->getItems()['test21']->getName());
        $this->assertEquals('test221', $section22->getItems()['test221']->getName());
    }
}
