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

namespace Sulu\Content\Tests\Unit\Content\Application\MetadataResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;

class MetadataResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolve(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator(['default' => new DefaultPropertyResolver()])
        );
        $metadataResolver = new MetadataResolver($propertyResolverProvider);

        $sectionMetadata = new SectionMetadata('section1');
        $fieldMetadata1 = new FieldMetadata('field1');
        $fieldMetadata1->setType('text_line');
        $sectionMetadata->addItem($fieldMetadata1);
        $locale = 'en';

        $fieldMetadata2 = new FieldMetadata('field2');
        $fieldMetadata2->setType('text_line');
        $items = [
            $sectionMetadata,
            $fieldMetadata2,
        ];
        $data = [
            'field1' => 'value1',
            'field2' => 'value2',
        ];

        $result = $metadataResolver->resolveItems($items, $data, $locale);
        self::assertCount(2, $result);
        self::assertArrayHasKey('field1', $result);
        self::assertArrayHasKey('field2', $result);
        self::assertSame('value1', $result['field1']->getContent());
        self::assertSame('value2', $result['field2']->getContent());
    }
}
