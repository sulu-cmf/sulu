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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Resolver\TemplateResolver;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class TemplateResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveWithNonTemplateInterface(): void
    {
        $templateResolver = new TemplateResolver(
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DimensionContent needs to extend the ' . TemplateInterface::class);

        $templateResolver->resolve($this->prophesize(DimensionContentInterface::class)->reveal());
    }

    public function testInvalidTemplateKeyResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('invalid');
        $dimensionContent->setTemplateData(['title' => 'Sulu']);

        $formMetadata = $this->prophesize(TypedFormMetadata::class);
        $formMetadata->getForms()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('example', 'en', [])
            ->willReturn($formMetadata->reveal());

        $templateResolver = new TemplateResolver(
            $formMetadataProvider->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template with key "invalid" not found. Available keys: ');

        $templateResolver->resolve($dimensionContent);
    }

    public function testResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setTemplateData(['title' => 'Sulu']);

        $formMetadata = new TypedFormMetadata();
        $defaultFormMetadata = new FormMetadata();
        $fieldMetadata = new FieldMetadata('title');
        $fieldMetadata->setType('text_line');
        $defaultFormMetadata->setItems([$fieldMetadata]);
        $formMetadata->addForm('default', $defaultFormMetadata);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('example', 'en', [])
            ->willReturn($formMetadata);

        $metadataResolver = new MetadataResolver(
            new PropertyResolverProvider(
                new \ArrayIterator(['default' => new DefaultPropertyResolver()])
            )
        );

        $templateResolver = new TemplateResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver
        );

        $contentView = $templateResolver->resolve($dimensionContent);

        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertInstanceOf(ContentView::class, $content['title']);
        $this->assertSame('Sulu', $content['title']->getContent());
        $this->assertSame([], $content['title']->getView());
    }
}
