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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\Automation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Form\SettingsFormMetadataVisitor;

class SettingsFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<XmlFormMetadataLoader>
     */
    private $xmlFormMetadataLoader;

    /**
     * @var SettingsFormMetadataVisitor
     */
    private $settingsFormMetadataVisitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlFormMetadataLoader = $this->prophesize(XmlFormMetadataLoader::class);
        $this->settingsFormMetadataVisitor = new SettingsFormMetadataVisitor($this->xmlFormMetadataLoader->reveal());
    }

    public function testVisitFormMetadataInvalidKey(): void
    {
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getKey()
            ->shouldBeCalled()
            ->willReturn('invalid_key');

        $this->xmlFormMetadataLoader->getMetadata(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->settingsFormMetadataVisitor->visitFormMetadata($formMetadata->reveal(), 'de', []);
    }

    public function testVisitFormMetadataWithoutForms(): void
    {
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getKey()
            ->shouldBeCalled()
            ->willReturn('content_settings');

        $this->xmlFormMetadataLoader->getMetadata(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->settingsFormMetadataVisitor->visitFormMetadata($formMetadata->reveal(), 'de', []);
    }

    public function testVisitFormMetadataInvalidSubFormMetadata(): void
    {
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getKey()
            ->shouldBeCalled()
            ->willReturn('content_settings');

        $formMetadata->setItems(Argument::any())
            ->shouldNotBeCalled();
        $formMetadata->setSchema(Argument::any())
            ->shouldNotBeCalled();

        $subFormMetadata = $this->prophesize(MetadataInterface::class);

        $metadataOptions = [
            'forms' => ['content_settings_author'],
        ];

        $this->xmlFormMetadataLoader->getMetadata('content_settings_author', 'de', $metadataOptions)
            ->shouldBeCalled()
            ->willReturn($subFormMetadata->reveal());

        $this->settingsFormMetadataVisitor->visitFormMetadata($formMetadata->reveal(), 'de', $metadataOptions);
    }

    public function testVisitFormMetadataSubFormMetadataIsNull(): void
    {
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getKey()
            ->shouldBeCalled()
            ->willReturn('content_settings');

        $formMetadata->setItems(Argument::any())
            ->shouldNotBeCalled();
        $formMetadata->setSchema(Argument::any())
            ->shouldNotBeCalled();

        $metadataOptions = [
            'forms' => ['content_settings_author'],
        ];

        $this->xmlFormMetadataLoader->getMetadata('content_settings_author', 'de', $metadataOptions)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->settingsFormMetadataVisitor->visitFormMetadata($formMetadata->reveal(), 'de', $metadataOptions);
    }

    public function testVisitFormMetadata(): void
    {
        $oldItemMetadata1 = $this->prophesize(ItemMetadata::class);
        $oldItemMetadata2 = $this->prophesize(ItemMetadata::class);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getKey()
            ->shouldBeCalled()
            ->willReturn('content_settings');
        $formMetadata->getItems()
            ->shouldBeCalled()
            ->willReturn([$oldItemMetadata1, $oldItemMetadata2]);
        $oldSchemaMetadata = $this->prophesize(SchemaMetadata::class);
        $formMetadata->getSchema()
            ->shouldBeCalled()
            ->willReturn($oldSchemaMetadata);

        $metadataOptions = [
            'forms' => ['content_settings_author'],
        ];

        $newItemMetadata1 = $this->prophesize(ItemMetadata::class);
        $newItemMetadata2 = $this->prophesize(ItemMetadata::class);
        $subFormMetadata = $this->prophesize(FormMetadata::class);
        $subFormMetadata->getItems()
            ->shouldBeCalled()
            ->willReturn([$newItemMetadata1, $newItemMetadata2]);
        $newSchemaMetadata = $this->prophesize(SchemaMetadata::class);
        $subFormMetadata->getSchema()
            ->shouldBeCalled()
            ->willReturn($newSchemaMetadata);

        $formMetadata->setItems([
            $oldItemMetadata1,
            $oldItemMetadata2,
            $newItemMetadata1,
            $newItemMetadata2,
        ])->shouldBeCalled();

        $mergedSchemaMetadata = $this->prophesize(SchemaMetadata::class);
        $oldSchemaMetadata->merge($newSchemaMetadata)
            ->shouldBeCalled()
            ->willReturn($mergedSchemaMetadata);
        $formMetadata->setSchema($mergedSchemaMetadata)
            ->shouldBeCalled();

        $this->xmlFormMetadataLoader->getMetadata('content_settings_author', 'de', $metadataOptions)
            ->shouldBeCalled()
            ->willReturn($subFormMetadata->reveal());

        $this->settingsFormMetadataVisitor->visitFormMetadata($formMetadata->reveal(), 'de', $metadataOptions);
    }
}
