<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Types\Metadata\GlobalBlocksTypedFormMetadataVisitor;

class GlobalBlocksTypedFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private ObjectProphecy $metadataProviderProphecy;
    private GlobalBlocksTypedFormMetadataVisitor $globalBlocksTypedFormMetadataVisitor;

    protected function setUp(): void
    {
        $metadataProviderRegistryProphecy = $this->prophesize(MetadataProviderRegistry::class);
        $this->metadataProviderProphecy = $this->prophesize(MetadataProviderInterface::class);

        $metadataProviderRegistryProphecy->getMetadataProvider('form')->willReturn($this->metadataProviderProphecy->reveal());

        $this->globalBlocksTypedFormMetadataVisitor = new GlobalBlocksTypedFormMetadataVisitor(
            $metadataProviderRegistryProphecy->reveal(),
        );
    }

    public function testDefinitionIsAddedForGlobalBlock(): void
    {
        $locale = 'en';
        $globalBlockName = 'test_block';

        $formMetadata = new TypedFormMetadata();

        $formTypeMetadata = new FormMetadata();
        $formTypeMetadata->setSchema(new SchemaMetadata());
        $formMetadata->addForm('test', $formTypeMetadata);

        $sectionMetadata = new SectionMetadata('test1');
        $formTypeMetadata->addItem($sectionMetadata);

        $fieldMetadata = new FieldMetadata('test2');
        $formTypeMetadata->addItem($fieldMetadata);

        $fieldTag = new TagMetadata();
        $fieldTag->setName('sulu.global_block');
        $fieldTag->setAttributes(['global_block' => $globalBlockName]);

        $fieldType1 = new FormMetadata();
        $fieldType1->setName('test3');
        $fieldType1->addTag($fieldTag);
        $fieldMetadata->addType($fieldType1);

        $fieldType2 = new FormMetadata();
        $fieldType2->setName('test4');
        $fieldMetadata->addType($fieldType2);

        $globalBlockFormMetadata = new TypedFormMetadata();
        $globalBlockFormTypeMetadata = new FormMetadata();
        $globalBlockFormMetadata->addForm($globalBlockName, $globalBlockFormTypeMetadata);

        $globalBlockFormTypeMetadata->setSchema(new SchemaMetadata());
        $globalBlockFormTypeMetadata->setName($globalBlockName);
        $globalBlockFormTypeMetadata->setTitle('Test Block Title');

        $this->metadataProviderProphecy->getMetadata('block', $locale, [
            'ignore_global_blocks' => true,
        ])->shouldBeCalledTimes(1)->willReturn($globalBlockFormMetadata);

        // Simulate visiting the TypedFormMetadata with a FieldMetadata that has a global block tag
        $this->globalBlocksTypedFormMetadataVisitor->visitTypedFormMetadata(
            $formMetadata,
            'key',
            $locale,
        );

        $definitions = $formTypeMetadata->getSchema()->toJsonSchema()['definitions'] ?? [];
        $this->assertSame(
            [
                $globalBlockName => [
                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                ],
            ],
            $definitions
        );

        $this->assertSame('Test Block Title', $fieldType1->getTitle());
    }

    public function testDefinitionIsAddedInFormDataForGlobalBlock(): void
    {
        $locale = 'en';
        $globalBlockName = 'test_block';

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test');

        $sectionMetadata = new SectionMetadata('test1');
        $formMetadata->addItem($sectionMetadata);

        $fieldMetadata = new FieldMetadata('test2');
        $formMetadata->addItem($fieldMetadata);

        $fieldTag = new TagMetadata();
        $fieldTag->setName('sulu.global_block');
        $fieldTag->setAttributes(['global_block' => $globalBlockName]);

        $fieldType1 = new FormMetadata();
        $fieldType1->setName('test3');
        $fieldType1->addTag($fieldTag);
        $fieldMetadata->addType($fieldType1);

        $fieldType2 = new FormMetadata();
        $fieldType2->setName('test4');
        $fieldMetadata->addType($fieldType2);

        $globalBlockFormMetadata = new TypedFormMetadata();
        $globalBlockFormTypeMetadata = new FormMetadata();
        $globalBlockFormMetadata->addForm($globalBlockName, $globalBlockFormTypeMetadata);

        $globalBlockFormTypeMetadata->setName($globalBlockName);
        $globalBlockFormTypeMetadata->setTitle('Test Block Title');

        $this->metadataProviderProphecy->getMetadata('block', $locale, [
            'ignore_global_blocks' => true,
        ])->shouldBeCalledTimes(1)->willReturn($globalBlockFormMetadata);

        // Simulate visiting the TypedFormMetadata with a FieldMetadata that has a global block tag
        $this->globalBlocksTypedFormMetadataVisitor->visitFormMetadata(
            $formMetadata,
            $locale,
        );

        $definitions = $formMetadata->getSchema()->toJsonSchema()['definitions'] ?? [];
        $this->assertSame(
            [
                $globalBlockName => [
                    'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                ],
            ],
            $definitions
        );

        $this->assertSame('Test Block Title', $fieldType1->getTitle());
    }

    public function testDefinitionIgnore(): void
    {
        $locale = 'en';
        $globalBlockName = 'test_block';

        $formMetadata = new TypedFormMetadata();

        $formTypeMetadata = new FormMetadata();
        $formMetadata->addForm('test', $formTypeMetadata);

        $sectionMetadata = new SectionMetadata('test1');
        $formTypeMetadata->addItem($sectionMetadata);

        $fieldMetadata = new FieldMetadata('test2');
        $formTypeMetadata->addItem($fieldMetadata);

        $fieldTag = new TagMetadata();
        $fieldTag->setName('sulu.global_block');
        $fieldTag->setAttributes(['global_block' => $globalBlockName]);

        $fieldType1 = new FormMetadata();
        $fieldType1->setName('test3');
        $fieldType1->addTag($fieldTag);
        $fieldMetadata->addType($fieldType1);

        $fieldType2 = new FormMetadata();
        $fieldType2->setName('test4');
        $fieldMetadata->addType($fieldType2);

        $globalBlockFormMetadata = new TypedFormMetadata();
        $globalBlockFormTypeMetadata = new FormMetadata();
        $globalBlockFormMetadata->addForm($globalBlockName, $globalBlockFormTypeMetadata);

        $globalBlockFormTypeMetadata->setName($globalBlockName);
        $globalBlockFormTypeMetadata->setTitle('Test Block Title');

        $this->metadataProviderProphecy->getMetadata('block', $locale, [
            'ignore_global_blocks' => true,
        ])->shouldNotBeCalled();

        // Simulate visiting the TypedFormMetadata with a FieldMetadata that has a global block tag
        $this->globalBlocksTypedFormMetadataVisitor->visitTypedFormMetadata(
            $formMetadata,
            'key',
            $locale,
            ['ignore_global_blocks' => true],
        );
    }
}
