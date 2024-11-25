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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Sulu\Search;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Search\ContentReindexProvider;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\ContentBundle\Tests\Traits\CreateExampleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\HttpKernel\SuluKernel;

class ContentReindexProviderTest extends SuluTestCase
{
    use CreateExampleTrait;

    /**
     * @var ContentReindexProvider<ExampleDimensionContent, Example>
     */
    private $reindexProvider;

    /**
     * @var Example
     */
    private static $example1;

    /**
     * @var Example
     */
    private static $example2;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();

        self::$example1 = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'title' => 'example-1',
                    ],
                ],
                'de' => [
                    'live' => [
                        'title' => 'beispiel-1',
                    ],
                ],
            ]
        );

        self::$example2 = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'title' => 'example-2',
                    ],
                ],
            ]
        );

        static::getEntityManager()->flush();
    }

    protected function setUp(): void
    {
        $this->reindexProvider = $this->getContainer()->get('example_test.example_reindex_provider');
    }

    public function testProvide(): void
    {
        $examples = $this->reindexProvider->provide(ExampleDimensionContent::class, 0, 10);

        $this->assertContains(self::$example1, $examples);
        $this->assertContains(self::$example2, $examples);
        $this->assertCount(2, $examples);
    }

    public function testGetCount(): void
    {
        $count = $this->reindexProvider->getCount(ExampleDimensionContent::class);

        $this->assertSame(2, (int) $count);
    }

    public function testCleanUp(): void
    {
        $this->expectNotToPerformAssertions();

        $this->reindexProvider->cleanUp(ExampleDimensionContent::class);
    }

    public function testGetClassFqns(): void
    {
        $this->assertSame(
            [ExampleDimensionContent::class],
            $classFqns = $this->reindexProvider->getClassFqns()
        );
    }

    public function testGetLocalesForObject(): void
    {
        $locales = $this->reindexProvider->getLocalesForObject(self::$example1);

        $this->assertSame(['en', 'de'], $locales);
    }

    public function testGetLocalesForObjectNoContentRichEntity(): void
    {
        $locales = $this->reindexProvider->getLocalesForObject(new \stdClass());

        $this->assertSame([], $locales);
    }

    public function testGetLocalesForObjectWebsite(): void
    {
        $reflection = new \ReflectionClass($this->reindexProvider);
        $property = $reflection->getProperty('context');
        $property->setAccessible(true);
        $property->setValue($this->reindexProvider, SuluKernel::CONTEXT_WEBSITE);

        $locales = $this->reindexProvider->getLocalesForObject(self::$example1);

        $this->assertSame(['de'], $locales);
    }

    public function testTranslateObject(): void
    {
        $translatedObject = $this->reindexProvider->translateObject(self::$example1, 'en');

        $this->assertInstanceOf(DimensionContentInterface::class, $translatedObject);

        $this->assertSame(
            DimensionContentInterface::STAGE_DRAFT,
            $translatedObject->getStage()
        );

        $this->assertSame(
            'en',
            $translatedObject->getLocale()
        );
    }

    public function testTranslateObjectWebsite(): void
    {
        $reflection = new \ReflectionClass($this->reindexProvider);
        $property = $reflection->getProperty('context');
        $property->setAccessible(true);
        $property->setValue($this->reindexProvider, SuluKernel::CONTEXT_WEBSITE);

        $translatedObject = $this->reindexProvider->translateObject(self::$example1, 'de');

        $this->assertInstanceOf(DimensionContentInterface::class, $translatedObject);

        $this->assertSame(
            DimensionContentInterface::STAGE_LIVE,
            $translatedObject->getStage()
        );

        $this->assertSame(
            'de',
            $translatedObject->getLocale()
        );
    }

    public function testTranslateObjectWebsiteNotPublished(): void
    {
        $reflection = new \ReflectionClass($this->reindexProvider);
        $property = $reflection->getProperty('context');
        $property->setAccessible(true);
        $property->setValue($this->reindexProvider, SuluKernel::CONTEXT_WEBSITE);

        $this->assertNull(
            $this->reindexProvider->translateObject(self::$example1, 'en')
        );
    }

    public function testTranslateObjectNoContentRichEntity(): void
    {
        $object = new \stdClass();

        $this->assertSame($object, $this->reindexProvider->translateObject($object, 'en'));
    }
}
