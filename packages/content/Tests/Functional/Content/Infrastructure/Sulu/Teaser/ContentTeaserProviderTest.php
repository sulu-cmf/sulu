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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Sulu\Teaser;

use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Teaser\ExampleTeaserProvider;
use Sulu\Bundle\ContentBundle\Tests\Traits\AssertSnapshotTrait;
use Sulu\Bundle\ContentBundle\Tests\Traits\CreateExampleTrait;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;

class ContentTeaserProviderTest extends WebsiteTestCase
{
    use AssertSnapshotTrait;
    use CreateExampleTrait;

    /**
     * @var ExampleTeaserProvider
     */
    private $exampleTeaserProvider;

    /**
     * @var array<int|string>
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
                    'excerptTitle' => 'example-1-excerpt-title',
                    'excerptDescription' => 'example-1-excerpt-description',
                    'excerptMore' => 'example-1-more',
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'beispiel-1',
                    'article' => null,
                    'excerptDescription' => 'example-1-excerpt-auszug',
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

        self::$exampleIds[] = $example1->getId();
        self::$exampleIds[] = $example2->getId();
        self::$exampleIds[] = $example3->getId();
        self::$exampleIds[] = $example4->getId();
        self::$exampleIds[] = $example5->getId();
    }

    protected function setUp(): void
    {
        $this->exampleTeaserProvider = $this->getContainer()->get('example_test.example_teaser_provider');
    }

    public function testEmpty(): void
    {
        $teasers = $this->exampleTeaserProvider->find([], 'de');

        $this->assertCount(0, $teasers);
    }

    public function testFindDE(): void
    {
        $teasers = $this->exampleTeaserProvider->find(self::$exampleIds, 'de');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_de.json', $teasers);
    }

    public function testFindEN(): void
    {
        $teasers = $this->exampleTeaserProvider->find(self::$exampleIds, 'en');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_en.json', $teasers);
    }

    public function testFindENNoRoute(): void
    {
        $example6 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example-6',
                    'template' => 'no-route',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $teasers = $this->exampleTeaserProvider->find([$example6->getId()], 'en');

        $teasers = $this->mapTeasers($teasers);

        $this->assertArraySnapshot('teasers_en_no_route.json', $teasers);
    }

    /**
     * @param Teaser[] $teasers
     *
     * @return array<string, mixed>
     */
    private function mapTeasers(array $teasers): array
    {
        return \array_map(function(Teaser $teaser) {
            return [
                'id' => $teaser->getId(),
                'type' => $teaser->getType(),
                'locale' => $teaser->getLocale(),
                'url' => $teaser->getUrl(),
                'title' => $teaser->getTitle(),
                'description' => $teaser->getDescription(),
                'moreText' => $teaser->getMoreText(),
                'mediaId' => $teaser->getMediaId(),
                'attributes' => $teaser->getAttributes(),
            ];
        }, $teasers);
    }
}
