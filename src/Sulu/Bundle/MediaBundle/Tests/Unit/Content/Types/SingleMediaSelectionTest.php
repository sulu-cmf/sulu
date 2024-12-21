<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class SingleMediaSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SingleMediaSelection
     */
    private $singleMediaSelection;

    /**
     * @var ObjectProphecy<MediaManager>
     */
    private $mediaManager;

    /**
     * @var ObjectProphecy<ReferenceStore>
     */
    private $mediaReferenceStore;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $nodeProperty;

    /**
     * @var ObjectProphecy<Media>
     */
    private $media;

    protected function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManager::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->media = $this->prophesize(Media::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->nodeProperty = $this->prophesize(PropertyInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);

        $this->webspace = new Webspace();
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace);

        $this->singleMediaSelection = new SingleMediaSelection(
            $this->mediaManager->reveal(),
            $this->mediaReferenceStore->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->securityChecker->reveal()
        );
    }

    public function testReadEmpty(): void
    {
        $property = $this->prophesize(Property::class);
        $property->getName()->willReturn('media');

        $this->node->hasProperty('media')->willReturn(false);

        $property->setValue(['id' => null])->shouldBeCalled();

        $this->singleMediaSelection->read(
            $this->node->reveal(),
            $property->reveal(),
            'sulu',
            'de',
            ''
        );
    }

    public function testRead(): void
    {
        $property = $this->prophesize(Property::class);
        $property->getName()->willReturn('media');

        $this->node->hasProperty('media')->willReturn(true);
        $this->node->getPropertyValue('media')->willReturn('{"id":11}');

        $property->setValue(['id' => 11])->shouldBeCalled();

        $this->singleMediaSelection->read(
            $this->node->reveal(),
            $property->reveal(),
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteEmpty(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->node->getProperty('media')->willReturn($this->nodeProperty->reveal());
        $this->node->hasProperty('media')->willReturn(true);

        $this->nodeProperty->remove()->shouldBeCalled();

        $this->singleMediaSelection->write(
            $this->node->reveal(),
            $property,
            null,
            'sulu',
            'de',
            ''
        );
    }

    public function testWrite(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->node->setProperty('media', '{"id":11}')->shouldBeCalled();

        $this->singleMediaSelection->write(
            $this->node->reveal(),
            $property,
            1,
            'sulu',
            'de',
            ''
        );
    }

    public function testDefaultParams(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertEquals(
            [],
            $this->singleMediaSelection->getDefaultParams($property)
        );
    }

    public function testDefaultValue(): void
    {
        $this->assertEquals(
            '{"id": null}',
            $this->singleMediaSelection->getDefaultValue()
        );
    }

    public function testViewDataEmpty(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertNull(
            $this->singleMediaSelection->getViewData($property)
        );
    }

    public function testViewData(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->assertEquals(
            ['id' => 11],
            $this->singleMediaSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->assertNull(
            $this->singleMediaSelection->getContentData($property)
        );
    }

    public function testContentData(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willReturn($this->media->reveal());
        $this->media->getCollection()->willReturn(5);

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                'sulu.media.collections',
                'de',
                Collection::class,
                5
            ),
            PermissionTypes::VIEW
        )->willReturn(true);

        $this->assertEquals($this->media->reveal(), $this->singleMediaSelection->getContentData($property));
    }

    public function testContentDataForMissingPermissions(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willReturn($this->media->reveal());
        $this->media->getCollection()->willReturn(7);

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                'sulu.media.collections',
                'de',
                Collection::class,
                7
            ),
            PermissionTypes::VIEW
        )->willReturn(false);

        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(true);
        $this->webspace->setSecurity($security);

        $this->assertNull($this->singleMediaSelection->getContentData($property));
    }

    public function testContentDataForMissingPermissionsWithPermissionCheckFalse(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willReturn($this->media->reveal());
        $this->media->getCollection()->willReturn(7);

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                'sulu.media.collections',
                'de',
                Collection::class,
                7
            ),
            PermissionTypes::VIEW
        )->willReturn(false);

        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(false);
        $this->webspace->setSecurity($security);

        $this->assertEquals($this->media->reveal(), $this->singleMediaSelection->getContentData($property));
    }

    public function testContentDataDeleted(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);
        $property->setStructure($structure->reveal());

        $this->mediaManager->getById(11, 'de')->willThrow(MediaNotFoundException::class);

        $this->assertNull($this->singleMediaSelection->getContentData($property));
    }

    public function testPreResolveEmpty(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(null);

        $this->mediaReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleMediaSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $property = new Property('media', [], 'single_media_selection');
        $property->setValue(['id' => 11]);

        $this->mediaReferenceStore->add(11)->shouldBeCalled();

        $this->singleMediaSelection->preResolve($property);
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->singleMediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'anyOf' => [
                                $this->getNullSchema(),
                                ['type' => 'number'],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->singleMediaSelection->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'number',
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['id'],
        ], $jsonSchema);
    }

    public function testGetReferencesWithNullProperty(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'single_media_selection',
        );
        $property->setValue(null);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(Argument::cetera())->shouldNotHaveBeenCalled();

        $this->singleMediaSelection->getReferences($property, $referenceCollector->reveal());
    }

    public function testGetReferences(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'single_media_selection',
        );
        $property->setValue(['id' => 1]);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(
            'media',
            1,
            'media'
        )->shouldBeCalled();

        $this->singleMediaSelection->getReferences($property, $referenceCollector->reveal());
    }
}
