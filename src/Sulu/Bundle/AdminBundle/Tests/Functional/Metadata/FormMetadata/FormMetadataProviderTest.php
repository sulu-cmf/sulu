<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;

class FormMetadataProviderTest extends KernelTestCase
{
    /**
     * @var FormMetadataProvider
     */
    private $formMetadataProvider;

    public function setUp(): void
    {
        $this->formMetadataProvider = $this->getContainer()->get('sulu_admin_test.form_metadata_provider');
    }

    public function testMetadataNotFound()
    {
        $this->expectException(MetadataNotFoundException::class);
        $this->formMetadataProvider->getMetadata('form_without_metadata', 'en');
    }

    public function testGetMetadataFromFormMetadataXmlLoader()
    {
        $form = $this->formMetadataProvider->getMetadata('form_with_schema', 'en');
        $this->assertInstanceOf(FormMetadata::class, $form);
        $this->assertCount(3, $form->getItems());
        $schema = $form->getSchema()->toJsonSchema();
        $this->assertCount(2, array_keys($schema));
    }

    public function testGetMetadataFromStructureLoader()
    {
        $typedForm = $this->formMetadataProvider->getMetadata('page', 'en');
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(2, $typedForm->getForms());
    }

    public function testGetMetadataTagFiltered()
    {
        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test' => ['value' => 'test-value']]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(1, $typedForm->getForms());
        $this->assertEquals(['default'], array_keys($typedForm->getForms()));

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test' => ['value' => 'test-value2']]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(0, $typedForm->getForms());

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test2' => ['value' => 'test-value2']]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(0, $typedForm->getForms());

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test2' => ['test' => 'test-value2']]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(1, $typedForm->getForms());
        $this->assertEquals(['default'], array_keys($typedForm->getForms()));

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test' => false]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(1, $typedForm->getForms());
        $this->assertEquals(['overview'], array_keys($typedForm->getForms()));

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test' => true]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(1, $typedForm->getForms());
        $this->assertEquals(['default'], array_keys($typedForm->getForms()));

        $typedForm = $this->formMetadataProvider->getMetadata(
            'page',
            'de',
            ['tags' => ['test3' => ['value' => 'test-value']]]
        );
        $this->assertInstanceOf(TypedFormMetadata::class, $typedForm);
        $this->assertCount(1, $typedForm->getForms());
        $this->assertEquals(['default'], array_keys($typedForm->getForms()));
    }
}
