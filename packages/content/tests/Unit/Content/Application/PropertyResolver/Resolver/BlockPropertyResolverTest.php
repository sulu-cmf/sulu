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

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\BlockPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Symfony\Component\ErrorHandler\BufferingLogger;

class BlockPropertyResolverTest extends TestCase
{
    private BlockPropertyResolver $resolver;
    private BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new BufferingLogger();
        $this->resolver = new BlockPropertyResolver(
            $this->logger,
            debug: false,
        );
        $metadataResolverProperty = new PropertyResolverProvider(
            new \ArrayIterator([
                'default' => new DefaultPropertyResolver(),
            ])
        );
        $metadataResolver = new MetadataResolver($metadataResolverProperty);
        $this->resolver->setMetadataResolver($metadataResolver);
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['metadata' => new FieldMetadata('example'), 'custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([
            'custom' => 'params',
        ], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_value' => [1];
        yield 'object' => [(object) [1, 2]];
        yield 'non_type_blocks' => [['text' => 'test'], ['text' => '123']];
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', ['metadata' => new FieldMetadata('example')]);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    public function testResolve(): void
    {
        $data = [
            [
                'type' => 'text_block',
                'title' => 'Sulu',
                'description' => 'Sulu is awesome',
            ],
        ];
        $locale = 'en';

        $formMetadata = new FormMetadata();
        $formMetadata->setName('text_block');
        $formMetadata->setKey('text_block');
        $blockFieldMetadata = new FieldMetadata('text_block');
        $blockFieldMetadata->addType($formMetadata);

        $tileFieldMetadata = new FieldMetadata('title');
        $tileFieldMetadata->setType('text_line');

        $descriptionFieldMetadata = new FieldMetadata('description');
        $descriptionFieldMetadata->setType('text_area');

        $formMetadata->addItem($tileFieldMetadata);
        $formMetadata->addItem($descriptionFieldMetadata);
        $params = [
            'metadata' => $blockFieldMetadata,
        ];

        $content = $this->resolver->resolve($data, $locale, $params);
        /** @var ContentView[] $innerContent */
        $innerContent = $content->getContent();
        $this->assertCount(1, $innerContent);
        /** @var array<string, mixed> $blockData */
        $blockData = $innerContent[0]->getContent();
        $this->assertSame('text_block', $blockData['type']);
        $this->assertInstanceOf(ContentView::class, $blockData['title']);
        $this->assertSame('Sulu', $blockData['title']->getContent());
        $this->assertSame([], $blockData['title']->getView());
        $this->assertInstanceOf(ContentView::class, $blockData['description']);
        $this->assertSame('Sulu is awesome', $blockData['description']->getContent());
        $this->assertSame([], $blockData['description']->getView());

        $this->assertSame([], $content->getView());
    }

    public function testResolveMinMaxOccursOne(): void
    {
        $data = [
            [
                'type' => 'text_block',
                'title' => 'Sulu',
                'description' => 'Sulu is awesome',
            ],
        ];
        $locale = 'en';

        $formMetadata = new FormMetadata();
        $formMetadata->setName('text_block');
        $formMetadata->setKey('text_block');
        $blockFieldMetadata = new FieldMetadata('text_block');
        $blockFieldMetadata->addType($formMetadata);
        $blockFieldMetadata->setMinOccurs(1);
        $blockFieldMetadata->setMaxOccurs(1);

        $tileFieldMetadata = new FieldMetadata('title');
        $tileFieldMetadata->setType('text_line');

        $descriptionFieldMetadata = new FieldMetadata('description');
        $descriptionFieldMetadata->setType('text_area');

        $formMetadata->addItem($tileFieldMetadata);
        $formMetadata->addItem($descriptionFieldMetadata);

        $params = [
            'metadata' => $blockFieldMetadata,
        ];

        $content = $this->resolver->resolve($data, $locale, $params);
        /** @var array<string, mixed> $innerContent */
        $innerContent = $content->getContent();
        // title / description / type
        $this->assertCount(3, $innerContent);
        $this->assertSame('text_block', $innerContent['type']);
        $this->assertInstanceOf(ContentView::class, $innerContent['title']);
        $this->assertSame('Sulu', $innerContent['title']->getContent());
        $this->assertSame([], $innerContent['title']->getView());
        $this->assertInstanceOf(ContentView::class, $innerContent['description']);
        $this->assertSame('Sulu is awesome', $innerContent['description']->getContent());
        $this->assertSame([], $innerContent['description']->getView());

        $this->assertSame([], $content->getView());
    }

    public function testGetType(): void
    {
        $this->assertSame('block', BlockPropertyResolver::getType());
    }
}
