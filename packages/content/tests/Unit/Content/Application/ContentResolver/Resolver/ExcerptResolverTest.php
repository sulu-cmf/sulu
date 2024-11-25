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
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentResolver\Resolver\ExcerptResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExcerptResolverTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    public function testResolveWithNonTemplateInterface(): void
    {
        $templateResolver = new ExcerptResolver(
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DimensionContent needs to extend the ' . ExcerptInterface::class);

        $templateResolver->resolve($this->prophesize(DimensionContentInterface::class)->reveal());
    }

    public function testResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $dimensionContent->setExcerptTitle('Sulu');
        $dimensionContent->setExcerptDescription('Sulu is awesome');
        $dimensionContent->setExcerptMore('Sulu is more awesome');
        $dimensionContent->setExcerptIcon(['id' => 1]);
        $dimensionContent->setExcerptImage(['id' => 2]);
        $tag = new Tag();
        $tag->setName('Tag 1');
        $this->setPrivateProperty($tag, 'id', 1);
        $dimensionContent->setExcerptTags([$tag]);
        $category = new Category();
        $this->setPrivateProperty($category, 'id', 1);
        $dimensionContent->setExcerptCategories([$category]);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getItems()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_excerpt', 'en', [])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems([], [
            'excerptTitle' => 'Sulu',
            'excerptDescription' => 'Sulu is awesome',
            'excerptMore' => 'Sulu is more awesome',
            'excerptIcon' => ['id' => 1],
            'excerptImage' => ['id' => 2],
            'excerptTags' => ['Tag 1'],
            'excerptCategories' => [1],
        ], 'en')
            ->willReturn(
                [
                    ContentView::create(['dummy' => 'data'], []),
                ]
            );

        $templateResolver = new ExcerptResolver(
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
