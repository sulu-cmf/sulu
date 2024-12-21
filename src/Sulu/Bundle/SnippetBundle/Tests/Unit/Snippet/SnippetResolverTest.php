<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

class SnippetResolverTest extends TestCase
{
    use ProphecyTrait;

    public static function dataProvider()
    {
        return [
            [[]],
            [['123-123-123']],
            [['123-123-123', '123-123-123']],
            [['123-123-123', '312-312-312']],
            [[], 'test_io'],
            [['123-123-123'], 'test_io'],
            [['123-123-123', '123-123-123'], 'test_io'],
            [['123-123-123', '312-312-312'], 'test_io'],
            [[], 'test_io', 'en'],
            [['123-123-123'], 'test_io', 'en'],
            [['123-123-123', '123-123-123'], 'test_io', 'en'],
            [['123-123-123', '312-312-312'], 'test_io', 'en'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testResolve($uuids, $webspaceKey = 'sulu_io', $locale = 'de'): void
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $structures = [];
        foreach (\array_unique($uuids) as $uuid) {
            $structure = $this->prophesize(SnippetBridge::class);
            $structure->getUuid()->willReturn($uuid);
            $structure->getKey()->willReturn('test');
            $structure->getHasTranslation()->willReturn(true);
            $structure->setIsShadow(false)->shouldBeCalled();
            $structure->setShadowBaseLanguage(null)->shouldBeCalled();

            $structures[$uuid] = $structure->reveal();
        }

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load(
            Argument::that(
                function($item) use ($uuids) {
                    return \in_array($item, $uuids);
                }
            ),
            $webspaceKey,
            $locale
        )->shouldBeCalledTimes(\count(\array_unique($uuids)))->will(
            function($arguments) use ($structures) {
                return $structures[$arguments[0]];
            }
        );

        $structureResolver->resolve(
            Argument::that(
                function(StructureInterface $structure) use ($uuids) {
                    return \in_array($structure->getUuid(), $uuids);
                }
            ),
            false
        )->shouldBeCalledTimes(\count(\array_unique($uuids)))->willReturn(
            ['content' => ['title' => 'test'], 'view' => ['title' => []]]
        );

        $result = $resolver->resolve($uuids, $webspaceKey, $locale);

        foreach ($result as $item) {
            $this->assertContains($item['view']['uuid'], $uuids);
            $this->assertEquals(
                [
                    'content' => ['title' => 'test'],
                    'view' => ['title' => [], 'template' => 'test', 'uuid' => $item['view']['uuid']],
                ],
                $item
            );
        }
    }

    public function testResolveWithMultipleLocales(): void
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $uuid = '1234-5678';
        $webspaceKey = 'sulu_io';
        $locales = ['en', 'de'];

        $structures = [];
        foreach ($locales as $locale) {
            $structure = $this->prophesize(SnippetBridge::class);
            $structure->getUuid()->willReturn('1234-5678');
            $structure->getKey()->willReturn('test');
            $structure->getHasTranslation()->willReturn(true);
            $structure->setIsShadow(false)->shouldBeCalled();
            $structure->setShadowBaseLanguage(null)->shouldBeCalled();

            $structures[$locale] = $structure->reveal();
        }

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load(
            $uuid,
            $webspaceKey,
            Argument::that(function($value) use ($locales) { return \in_array($value, $locales); })
        )->shouldBeCalledTimes(\count($locales))->will(
            function($arguments) use ($structures) {
                return $structures[$arguments[2]];
            }
        );

        $structureResolver->resolve($structures['en'], false)
            ->shouldBeCalledTimes(1)
            ->willReturn(['content' => ['title' => 'English'], 'view' => ['title' => []]]);

        $structureResolver->resolve($structures['de'], false)
            ->shouldBeCalledTimes(1)
            ->willReturn(['content' => ['title' => 'Deutsch'], 'view' => ['title' => []]]);

        $result = $resolver->resolve([$uuid], $webspaceKey, 'en');
        $result2 = $resolver->resolve([$uuid], $webspaceKey, 'de');

        // Ids are equal but not the entire content
        $this->assertEquals($result[0]['view']['uuid'], $result2[0]['view']['uuid']);
        $this->assertNotEquals($result, $result2);
    }

    public function testResolveNotExistingUuid(): void
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);
        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load('123-123-123', 'test_io', 'en')
            ->shouldBeCalledTimes(1)
            ->willThrow(new DocumentNotFoundException());

        $this->assertSame(
            [],
            $resolver->resolve(['123-123-123'], 'test_io', 'en')
        );
    }

    public function testResolveWithShadowLocale(): void
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $structure1 = $this->prophesize(SnippetBridge::class);
        $structure1->getUuid()->willReturn('123-123-123');
        $structure1->getKey()->willReturn('test');
        $structure1->getHasTranslation()->willReturn(false);

        $structure2 = $this->prophesize(SnippetBridge::class);
        $structure2->getUuid()->willReturn('123-123-123');
        $structure2->getKey()->willReturn('test');
        $structure2->getHasTranslation()->willReturn(false);
        $structure2->setIsShadow(true)->shouldBeCalled();
        $structure2->setShadowBaseLanguage('en')->shouldBeCalled();

        /** @var SnippetDocument|ObjectProphecy $snippetDocument */
        $snippetDocument = $this->prophesize(SnippetDocument::class);
        $structure2->getDocument()->willReturn($snippetDocument->reveal());
        $snippetDocument->setLocale('en')->shouldBeCalled();
        $snippetDocument->setOriginalLocale('de')->shouldBeCalled();

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load('123-123-123', 'sulu_io', 'de')->shouldBeCalledTimes(1)->willReturn($structure1->reveal());
        $contentMapper->load('123-123-123', 'sulu_io', 'en')->shouldBeCalledTimes(1)->willReturn($structure2->reveal());

        $structureResolver->resolve($structure2->reveal(), false)
            ->willReturn(['content' => ['title' => 'test'], 'view' => ['title' => []]]);

        $resolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en');
    }

    public function testResolveWithExtensions(): void
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $structure = $this->prophesize(SnippetBridge::class);
        $structure->getUuid()->willReturn('123-123-123');
        $structure->getKey()->willReturn('test');
        $structure->getHasTranslation()->willReturn(true);
        $structure->setIsShadow(false)->shouldBeCalled();
        $structure->setShadowBaseLanguage(null)->shouldBeCalled();

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load('123-123-123', 'sulu_io', 'de')->shouldBeCalledTimes(1)->willReturn($structure->reveal());

        $structureResolver->resolve($structure->reveal(), true)
            ->willReturn(
                [
                    'content' => [
                        'title' => 'test',
                    ],
                    'extension' => ['excerpt' => ['categories' => [], 'tags' => []]],
                    'view' => ['title' => []],
                ]
            );

        $this->assertEquals(
            [
                [
                    'content' => [
                        'title' => 'test',
                        'taxonomies' => [
                            'categories' => [],
                            'tags' => [],
                        ],
                    ],
                    'extension' => [
                        'excerpt' => [
                            'categories' => [],
                            'tags' => [],
                        ],
                    ],
                    'view' => [
                        'title' => [],
                        'template' => 'test',
                        'uuid' => '123-123-123',
                    ],
                ],
            ],
            $resolver->resolve(['123-123-123'], 'sulu_io', 'de', null, true)
        );
    }
}
