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

namespace Sulu\Content\Tests\Functional\Infrastructure\Sulu\Sitemap;

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Content\Infrastructure\Sulu\Sitemap\ContentSitemapProvider;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Traits\CreateExampleTrait;

class ContentSitemapProviderTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreateExampleTrait;
    use SetGetPrivatePropertyTrait;

    /** @var string */
    public const SCHEME = 'https';

    /** @var string */
    public const HOST = 'localhost';

    /**
     * @var ContentSitemapProvider<ExampleDimensionContent, Example>
     */
    private $contentSitemapProvider;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();

        // Example 1 (both locales, both published)
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'title' => 'example-1',
                    ],
                ],
                'de' => [
                    'live' => [
                        'title' => 'beispiel-1',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        // Example 2 (only en, published)
        $example2 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'title' => 'example-2',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        // Example 3 (both locales, only en published)
        $example3 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'title' => 'example-3',
                    ],
                ],
                'de' => [
                    'draft' => [
                        'title' => 'beispiel-3',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        // Example 4 (only de, published)
        $example4 = static::createExample(
            [
                'de' => [
                    'live' => [
                        'title' => 'beispiel-4',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        // Example 5 (only en, not published)
        $example5 = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'title' => 'example-5',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();
    }

    protected function setUp(): void
    {
        $this->contentSitemapProvider = $this->getContainer()->get('example_test.example_sitemap_provider');
    }

    public function testBuild(): void
    {
        /** @var SitemapUrl[] $sitemapEntries */
        $sitemapEntries = $this->contentSitemapProvider->build(1, static::SCHEME, static::HOST);

        $sitemapEntries = $this->mapSitemapEntries($sitemapEntries);

        $this->assertArraySnapshot('sitemap.json', $sitemapEntries);
    }

    public function testCreateSitemap(): void
    {
        $sitemap = $this->contentSitemapProvider->createSitemap(static::SCHEME, static::HOST);

        $this->assertSame($this->contentSitemapProvider->getAlias(), $sitemap->getAlias());
        $this->assertSame($this->contentSitemapProvider->getMaxPage(static::SCHEME, static::HOST), $sitemap->getMaxPage());
    }

    public function testGetMaxPage(): void
    {
        // set page-size to 5 to check if the query behind it is correct
        $this->setPrivateProperty($this->contentSitemapProvider, 'pageSize', 5);

        $this->assertSame(1, $this->contentSitemapProvider->getMaxPage(static::SCHEME, static::HOST));
    }

    public function testGetAlias(): void
    {
        $this->assertSame('examples', $this->contentSitemapProvider->getAlias());
    }

    /**
     * @param SitemapUrl[] $sitemapEntries
     *
     * @return array<mixed>
     */
    private function mapSitemapEntries(array $sitemapEntries): array
    {
        \usort(
            $sitemapEntries,
            function(SitemapUrl $a, SitemapUrl $b) {
                return \strcmp($a->getLoc(), $b->getLoc());
            }
        );

        return \array_map(
            function(SitemapUrl $sitemapUrl) {
                return [
                    'locale' => $sitemapUrl->getLocale(),
                    'defaultLocale' => $sitemapUrl->getDefaultLocale(),
                    'loc' => $sitemapUrl->getLoc(),
                    'alternateLinks' => \array_map(function(SitemapAlternateLink $alternateLink) {
                        return [
                            'locale' => $alternateLink->getLocale(),
                            'href' => $alternateLink->getHref(),
                        ];
                    }, $sitemapUrl->getAlternateLinks()),
                ];
            },
            $sitemapEntries
        );
    }
}
