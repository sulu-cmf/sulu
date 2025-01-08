<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\ListMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\XmlListMetadataLoader;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\SinglePropertyMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

class XmlListMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var XmlListMetadataLoader
     */
    private $xmlListMetadataLoader;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var ObjectProphecy<FieldDescriptorFactoryInterface>
     */
    private $fieldDescriptorFactory;

    public function setUp(): void
    {
        $this->fieldDescriptorFactory = $this->prophesize(FieldDescriptorFactoryInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->xmlListMetadataLoader = new XmlListMetadataLoader(
            $this->fieldDescriptorFactory->reveal(),
            $this->translator->reveal()
        );
    }

    public function testGetMetadata(): void
    {
        $this->translator->trans('sulu_contact.first_name', [], 'admin', 'de')->willReturn('First name');
        $this->translator->trans('sulu_contact.last_name', [], 'admin', 'de')->willReturn('Last name');
        $this->translator->trans('sulu_contact.name', [], 'admin', 'en')->willReturn('Name');

        $firstNameFieldDescriptor = new FieldDescriptor(
            'firstName',
            'sulu_contact.first_name',
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'string',
            true,
            FieldDescriptorInterface::WIDTH_SHRINK
        );

        $firstNameMetadata = new SinglePropertyMetadata('firstName');
        $firstNameMetadata->setFilterType('string');
        $firstNameFieldDescriptor->setMetadata($firstNameMetadata);

        $lastNameFieldDescriptor = new FieldDescriptor(
            'lastName',
            'sulu_contact.last_name',
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'string',
            false
        );

        $lastNameMetadata = new SinglePropertyMetadata('lastName');
        $lastNameMetadata->setFilterType('integer');
        $lastNameFieldDescriptor->setMetadata($lastNameMetadata);

        $accountFieldDescriptor = new FieldDescriptor(
            'name',
            'sulu_contact.name',
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'string',
            true
        );

        $accountMetadata = new SinglePropertyMetadata('name');
        $accountMetadata->setFilterType('single_selection');
        $accountMetadata->setFilterTypeParameters(['resource_key' => 'accounts']);
        $accountMetadata->setTransformerTypeParameters(['color' => 'red']);
        $accountFieldDescriptor->setMetadata($accountMetadata);

        $this->fieldDescriptorFactory->getFieldDescriptors('contact')->willReturn(
            [
                $firstNameFieldDescriptor,
                $lastNameFieldDescriptor,
            ]
        );
        $this->fieldDescriptorFactory->getFieldDescriptors('account')->willReturn([$accountFieldDescriptor]);

        $contactListMetadata = $this->xmlListMetadataLoader->getMetadata('contact', 'de');
        $this->assertNotNull($contactListMetadata);
        $contactListFields = $contactListMetadata->getFields();

        $this->assertEquals('firstName', $contactListFields['firstName']->getName());
        $this->assertEquals('First name', $contactListFields['firstName']->getLabel());
        $this->assertEquals('string', $contactListFields['firstName']->getType());
        $this->assertEquals(true, $contactListFields['firstName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $contactListFields['firstName']->getVisibility()
        );
        $this->assertEquals('string', $contactListFields['firstName']->getFilterType());
        $this->assertEquals(null, $contactListFields['firstName']->getFilterTypeParameters());
        $this->assertEquals([], $contactListFields['firstName']->getTransformerTypeParameters());
        $this->assertEquals(FieldDescriptorInterface::WIDTH_SHRINK, $contactListFields['firstName']->getWidth());

        $this->assertEquals('lastName', $contactListFields['lastName']->getName());
        $this->assertEquals('Last name', $contactListFields['lastName']->getLabel());
        $this->assertEquals('string', $contactListFields['lastName']->getType());
        $this->assertEquals(false, $contactListFields['lastName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_NO,
            $contactListFields['lastName']->getVisibility()
        );
        $this->assertEquals('integer', $contactListFields['lastName']->getFilterType());
        $this->assertEquals(null, $contactListFields['lastName']->getFilterTypeParameters());
        $this->assertEquals([], $contactListFields['lastName']->getTransformerTypeParameters());

        $accountListMetadata = $this->xmlListMetadataLoader->getMetadata('account', 'en');
        $this->assertNotNull($accountListMetadata);
        $accountListFields = $accountListMetadata->getFields();

        $this->assertEquals('name', $accountListFields['name']->getName());
        $this->assertEquals('Name', $accountListFields['name']->getLabel());
        $this->assertEquals('string', $accountListFields['name']->getType());
        $this->assertEquals(true, $accountListFields['name']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $accountListFields['name']->getVisibility()
        );
        $this->assertEquals('single_selection', $accountListFields['name']->getFilterType());
        $this->assertEquals(['resource_key' => 'accounts'], $accountListFields['name']->getFilterTypeParameters());
        $this->assertEquals(['color' => 'red'], $accountListFields['name']->getTransformerTypeParameters());
    }

    public function testGetMetadataNotExisting(): void
    {
        $notExistingMetadata = $this->xmlListMetadataLoader->getMetadata('not-existing', 'de');
        $this->assertNull($notExistingMetadata);
    }
}
