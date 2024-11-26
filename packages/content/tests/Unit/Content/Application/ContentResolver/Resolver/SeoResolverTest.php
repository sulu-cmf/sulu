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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Resolver\SeoResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class SeoResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveWithNonTemplateInterface(): void
    {
        $templateResolver = new SeoResolver(
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DimensionContent needs to extend the ' . SeoInterface::class);

        $templateResolver->resolve($this->prophesize(DimensionContentInterface::class)->reveal());
    }

    public function testResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $dimensionContent->setSeoTitle('Sulu');
        $dimensionContent->setSeoDescription('Sulu is awesome');
        $dimensionContent->setSeoKeywords('Sulu, awesome');
        $dimensionContent->setSeoCanonicalUrl('https://sulu.io');
        $dimensionContent->setSeoNoIndex(true);
        $dimensionContent->setSeoNoFollow(true);
        $dimensionContent->setSeoHideInSitemap(true);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getItems()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_seo', 'en', [])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems([], [
            'seoTitle' => 'Sulu',
            'seoDescription' => 'Sulu is awesome',
            'seoKeywords' => 'Sulu, awesome',
            'seoCanonicalUrl' => 'https://sulu.io',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
        ], 'en')
            ->willReturn(
                [
                    ContentView::create(['dummy' => 'data'], []),
                ]
            );

        $templateResolver = new SeoResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal()
        );

        $contentView = $templateResolver->resolve($dimensionContent);

        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
    }
}
