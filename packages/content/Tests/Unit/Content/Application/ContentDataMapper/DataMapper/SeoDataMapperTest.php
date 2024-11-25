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
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\SeoDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class SeoDataMapperTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createSeoDataMapperInstance(): SeoDataMapper
    {
        return new SeoDataMapper();
    }

    public function testMapNoSeoInterface(): void
    {
        $data = [
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoCanonicalUrl' => 'http://example.localhost',
            'seoHideInSitemap' => true,
            'seoNoIndex' => true,
            'seoNoFollow' => true,
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
        $this->assertTrue(true); // Avoid risky test as this is an early return test
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $seoMapper = $this->createSeoDataMapperInstance();

        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getSeoTitle());
        $this->assertNull($localizedDimensionContent->getSeoDescription());
        $this->assertNull($localizedDimensionContent->getSeoKeywords());
        $this->assertNull($localizedDimensionContent->getSeoCanonicalUrl());
        $this->assertFalse($localizedDimensionContent->getSeoHideInSitemap());
        $this->assertFalse($localizedDimensionContent->getSeoNoFollow());
        $this->assertFalse($localizedDimensionContent->getSeoNoIndex());
    }

    public function testMapData(): void
    {
        $data = [
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoCanonicalUrl' => 'http://example.localhost',
            'seoHideInSitemap' => true,
            'seoNoIndex' => true,
            'seoNoFollow' => true,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $seoMapper = $this->createSeoDataMapperInstance();

        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Seo Title', $localizedDimensionContent->getSeoTitle());
        $this->assertSame('Seo Description', $localizedDimensionContent->getSeoDescription());
        $this->assertSame('Seo Keyword 1, Seo Keyword 2', $localizedDimensionContent->getSeoKeywords());
        $this->assertSame('http://example.localhost', $localizedDimensionContent->getSeoCanonicalUrl());
        $this->assertTrue($localizedDimensionContent->getSeoHideInSitemap());
        $this->assertTrue($localizedDimensionContent->getSeoNoFollow());
        $this->assertTrue($localizedDimensionContent->getSeoNoIndex());
    }
}
