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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Sulu\Link;

use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Link\ExampleLinkProvider;
use Sulu\Bundle\ContentBundle\Tests\Traits\AssertSnapshotTrait;
use Sulu\Bundle\ContentBundle\Tests\Traits\CreateExampleTrait;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;

class ContentLinkProviderTest extends WebsiteTestCase
{
    use AssertSnapshotTrait;
    use CreateExampleTrait;

    /**
     * @var ExampleLinkProvider
     */
    private $exampleLinkProvider;

    /**
     * @var string[]
     */
    private static $exampleIds = [];

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();

        // Example 1 (both locales, both published)
        $example1 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-1',
                    'article' => 'example-1-article',
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'beispiel-1',
                    'article' => null,
                ],
            ],
        ]);

        // Example 2 (only en, published)
        $example2 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-2',
                ],
            ],
        ]);

        // Example 3 (both locales, only en published)
        $example3 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-3',
                    'article' => '<p>Test article</p>',
                ],
            ],
            'de' => [
                'draft' => [
                    'title' => 'beispiel-3',
                ],
            ],
        ]);

        // Example 4 (only de, published)
        $example4 = static::createExample([
            'de' => [
                'live' => [
                    'title' => 'beispiel-4',
                    'article' => '<p>Test article</p>',
                ],
            ],
        ]);

        // Example 5 (only en, not published)
        $example5 = static::createExample([
            'en' => [
                'draft' => [
                    'title' => 'example-5',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        self::$exampleIds[] = (string) $example1->getId();
        self::$exampleIds[] = (string) $example2->getId();
        self::$exampleIds[] = (string) $example3->getId();
        self::$exampleIds[] = (string) $example4->getId();
        self::$exampleIds[] = (string) $example5->getId();
    }

    protected function setUp(): void
    {
        /** @var ExampleLinkProvider $provider */
        $provider = self::getContainer()->get('example_test.example_link_provider');
        $this->exampleLinkProvider = $provider;
    }

    public function testEmpty(): void
    {
        $links = $this->exampleLinkProvider->preload([], 'de');

        $this->assertCount(0, $links);
    }

    public function testPreloadDE(): void
    {
        $links = $this->exampleLinkProvider->preload(self::$exampleIds, 'de');
        $this->assertArraySnapshot('links_de.json', $this->mapLinks($links));
    }

    public function testPreloadEN(): void
    {
        $links = $this->exampleLinkProvider->preload(self::$exampleIds, 'en');
        $this->assertArraySnapshot('links_en.json', $this->mapLinks($links));
    }

    /**
     * @param LinkItem[] $links
     *
     * @return array<string, mixed>
     */
    private function mapLinks(array $links): array
    {
        return \array_map(function(LinkItem $linkItem) {
            return [
                'id' => $linkItem->getId(),
                'url' => $linkItem->getUrl(),
                'title' => $linkItem->getTitle(),
            ];
        }, $links);
    }
}
