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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\ShadowDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ShadowDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function createShadowDataMapperInstance(): ShadowDataMapper
    {
        return new ShadowDataMapper();
    }

    public function testMapNoAuthorInterface(): void
    {
        $data = [
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->getLocale()->willReturn('en');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);

        $this->assertTrue(true); // nothing called in this case
    }

    public function testMapShadowNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('de');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getShadowLocale());
    }

    public function testMapShadowNoDataExistData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('de');
        $localizedDimensionContent->setShadowLocale('en');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('en', $localizedDimensionContent->getShadowLocale());
    }

    public function testMapData(): void
    {
        $data = [
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('de');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('en', $localizedDimensionContent->getShadowLocale());
        $this->assertSame(['de' => 'en'], $unlocalizedDimensionContent->getShadowLocales());
    }

    public function testMapDataRemoveShadow(): void
    {
        $data = [
            'shadowOn' => false,
            'shadowLocale' => null,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->addShadowLocale('de', 'en');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('de');
        $localizedDimensionContent->setShadowLocale('en');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getShadowLocale());
        $this->assertNull($unlocalizedDimensionContent->getShadowLocales());
    }

    public function testMapDataNull(): void
    {
        $data = [
            'shadowOn' => null,
            'shadowLocale' => null,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->addShadowLocale('de', 'en');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('de');
        $localizedDimensionContent->setShadowLocale('en');

        $shadowMapper = $this->createShadowDataMapperInstance();
        $shadowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getShadowLocale());
        $this->assertNull($unlocalizedDimensionContent->getShadowLocales());
    }
}
