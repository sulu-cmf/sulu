<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Content\Types\SinglePageSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;

class SinglePageSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var SinglePageSelection
     */
    private $type;

    public function setUp(): void
    {
        parent::setUp();

        $this->property = $this->prophesize(PropertyInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->type = new SinglePageSelection($this->referenceStore->reveal());
    }

    public static function providePreResolve()
    {
        return [
            [
                '4234-2345-2345-3245',
                ['4234-2345-2345-3245'],
            ],
            [
                null,
                [],
            ],
            [
                '',
                [],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePreResolve')]
    public function testPreResolve($propertyValue, $expected): void
    {
        $this->property->getValue()->willReturn($propertyValue);
        $this->type->preResolve($this->property->reveal());

        if (0 === \count($expected)) {
            $this->referenceStore->add(Argument::any())->shouldNotBeCalled();
        }

        foreach ($expected as $uuid) {
            $this->referenceStore->add($uuid)->shouldBeCalled();
        }
    }

    public function testContentData(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');
        $structure->getWebspaceKey()->willReturn('sulu_io');

        $this->property->getValue()->willReturn('some-uuid');
        $this->property->getStructure()->willReturn($structure->reveal());

        $this->assertEquals('some-uuid', $this->type->getContentData($this->property->reveal()));
    }
}
