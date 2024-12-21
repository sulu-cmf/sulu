<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata\General\Driver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\AbstractPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ConcatenationPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\CountPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\GroupConcatPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\IdentityPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ListMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\ListXmlLoader;
use Sulu\Component\Rest\ListBuilder\Metadata\SinglePropertyMetadata;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ListXmlLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ParameterBagInterface>
     */
    private $parameterBag;

    /**
     * @var ListXmlLoader
     */
    private $listXmlLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBag = $this->prophesize(ParameterBagInterface::class);

        $this->parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('Sulu\Bundle\ContactBundle\Entity\Contact');
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'Sulu\Bundle\ContactBundle\Entity\Contact.avatar'
        );
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'Sulu\Bundle\ContactBundle\Entity\Contact.contactAddresses'
        );
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.tags')->willReturn(
            'Sulu\Bundle\ContactBundle\Entity\Contact.tags'
        );
        $this->parameterBag->resolveValue('%test-parameter%')->willReturn('test-value');
        $this->parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $this->listXmlLoader = new ListXmlLoader($this->parameterBag->reveal());
    }

    public function testLoadMetadataFromFileComplete(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/complete.xml');
        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('complete', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(6, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'translation' => 'public.id',
                'type' => 'integer',
            ],
            $propertiesMetadata[0]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'firstName',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'translation' => 'contact.contacts.firstName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
            ],
            $propertiesMetadata[1]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'lastName',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'translation' => 'contact.contacts.lastName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
            ],
            $propertiesMetadata[2]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'avatar',
                'entityName' => 'Sulu\Bundle\MediaBundle\Entity\Media',
                'translation' => 'public.avatar',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'type' => 'thumbnails',
                'sortable' => false,
                'joins' => [
                    [
                        'entityName' => 'Sulu\Bundle\MediaBundle\Entity\Media',
                        'entityField' => 'Sulu\Bundle\ContactBundle\Entity\Contact.avatar',
                    ],
                ],
            ],
            $propertiesMetadata[3]
        );
        $this->assertConcatenationMetadata(
            [
                'name' => 'fullName',
                'translation' => 'public.name',
                'width' => '100px',
                'minWidth' => '50px',
                'sortable' => false,
                'class' => 'test-class',
                'glue' => ' ',
                'fields' => [
                    [
                        'name' => 'firstName',
                        'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                    ],
                    [
                        'name' => 'lastName',
                        'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                    ],
                ],
            ],
            $propertiesMetadata[4]
        );
    }

    public function testLoadMetadataFromFileMinimal(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/minimal.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('minimal', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(3, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'type' => 'integer',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
            ],
            $propertiesMetadata[0]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_YES,
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'width' => FieldDescriptorInterface::WIDTH_SHRINK,
            ],
            $propertiesMetadata[1]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_NO,
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
            ],
            $propertiesMetadata[2]
        );
    }

    public function testLoadMetadataFromFileOldPropertyTypeSyntax(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/old-property-type-syntax.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('minimal', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(3, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'type' => 'integer',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
            ],
            $propertiesMetadata[0]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_YES,
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
            ],
            $propertiesMetadata[1]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_NO,
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
            ],
            $propertiesMetadata[2]
        );
    }

    public function testLoadMetadataFromFileFilterType(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/filter-type.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('filter-type', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileFilterTypeParameters(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/filter-type-params.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('filter-type-params', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
                'filter-type-params' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileFilterTypeCollectionParam(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/filter-type-collection-param.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('filter-type-collection-param', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test',
                'filter-type-params' => [
                    'testCollection' => [
                        'test1' => 'test-value',
                        'test2' => 'test',
                    ],
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileFilterTypeCollectionParamEmptyName(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/filter-type-collection-param-empty-name.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('filter-type-collection-param-empty-name', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test',
                'filter-type-params' => [
                    'testCollection' => [
                        'test-value',
                        'test',
                    ],
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileTransformerParameters(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/transformer-type-params.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('transformer-type-params', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'type' => 'test-transformer',
                'transformer-type-params' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileGroupConcat(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/group-concat.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('group-concat', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertGroupConcatMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'Sulu\Bundle\TagBundle\Entity\Tag',
                'joins' => [
                    [
                        'entityName' => 'Sulu\Bundle\TagBundle\Entity\Tag',
                        'entityField' => 'Sulu\Bundle\ContactBundle\Entity\Contact.tags',
                    ],
                ],
                'filter-type' => 'test',
                'filter-type-params' => [
                    'testCollection' => [
                        'test1' => 'test-value',
                        'test2' => 'test',
                    ],
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileIdentity(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/identity.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('identity', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertIdentityMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'filter-type' => 'test',
                'filter-type-params' => [
                    'testCollection' => [
                        'test1' => 'test-value',
                        'test2' => 'test',
                    ],
                ],
                'width' => FieldDescriptorInterface::WIDTH_SHRINK,
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileCount(): void
    {
        $result = $this->listXmlLoader->load(__DIR__ . '/Resources/count.xml');

        $this->assertInstanceOf(ListMetadata::class, $result);
        $this->assertEquals('count', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertCountMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'Sulu\Bundle\ContactBundle\Entity\Contact',
                'filter-type' => 'test',
                'filter-type-params' => [
                    'testCollection' => [
                        'test1' => 'test-value',
                        'test2' => 'test',
                    ],
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    private function assertSingleMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(SinglePropertyMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertGroupConcatMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(GroupConcatPropertyMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertIdentityMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(IdentityPropertyMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertCountMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(CountPropertyMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertPropertyMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $expected = \array_merge(
            [
                'instance' => AbstractPropertyMetadata::class,
                'name' => null,
                'translation' => null,
                'visibility' => FieldDescriptorInterface::VISIBILITY_NO,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_NEVER,
                'type' => 'string',
                'sortable' => true,
                'filter-type' => null,
                'filter-type-params' => null,
                'transformer-type-params' => null,
                'entityName' => null,
                'joins' => [],
                'width' => FieldDescriptorInterface::WIDTH_AUTO,
            ],
            $expected
        );

        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['translation'], $metadata->getTranslation());
        $this->assertEquals($expected['filter-type'], $metadata->getFilterType());
        $this->assertEquals($expected['filter-type-params'], $metadata->getFilterTypeParameters());
        $this->assertEquals($expected['transformer-type-params'], $metadata->getTransformerTypeParameters());
        $this->assertEquals($expected['visibility'], $metadata->getVisibility());
        $this->assertEquals($expected['searchability'], $metadata->getSearchability());
        $this->assertEquals($expected['width'], $metadata->getWidth());

        $this->assertEquals($expected['type'], $metadata->getType());
        $this->assertEquals($expected['sortable'], $metadata->isSortable());

        if ($metadata->getField()) {
            $this->assertFieldMetadata($expected, $metadata->getField());
        }
    }

    private function assertFieldMetadata(array $expected, FieldMetadata $fieldMetadata)
    {
        $expected = \array_merge(
            [
                'joins' => [],
            ],
            $expected
        );

        $this->assertEquals($expected['entityName'], $fieldMetadata->getEntityName());
        $this->assertCount(\count($expected['joins']), $fieldMetadata->getJoins());

        $i = 0;
        foreach ($expected['joins'] as $joinExpected) {
            $this->assertJoin($joinExpected, $fieldMetadata->getJoins()[$i]);
            ++$i;
        }
    }

    private function assertJoin(array $expected, JoinMetadata $metadata)
    {
        $expected = \array_merge(
            [
                'entityName' => null,
                'entityField' => null,
                'condition' => null,
                'conditionMethod' => 'WITH',
                'method' => 'LEFT',
            ],
            $expected
        );

        $this->assertEquals($expected['entityName'], $metadata->getEntityName());
        $this->assertEquals($expected['entityField'], $metadata->getEntityField());
        $this->assertEquals($expected['condition'], $metadata->getCondition());
        $this->assertEquals($expected['conditionMethod'], $metadata->getConditionMethod());
        $this->assertEquals($expected['method'], $metadata->getMethod());
    }

    private function assertConcatenationMetadata($expected, AbstractPropertyMetadata $metadata)
    {
        $expected = \array_merge(
            [
                'glue' => null,
                'fields' => [],
            ],
            $expected
        );

        $this->assertInstanceOf(ConcatenationPropertyMetadata::class, $metadata);

        $this->assertEquals($expected['glue'], $metadata->getGlue());
        $this->assertCount(\count($expected['fields']), $metadata->getFields());

        $i = 0;
        foreach ($expected['fields'] as $fieldExpected) {
            $this->assertFieldMetadata($fieldExpected, $metadata->getFields()[$i]);
            ++$i;
        }
    }
}
