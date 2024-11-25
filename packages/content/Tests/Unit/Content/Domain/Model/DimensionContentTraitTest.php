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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentTrait;

class DimensionContentTraitTest extends TestCase
{
    protected function getDimensionContentInstance(): DimensionContentInterface // @phpstan-ignore-line
    {
        return new class() implements DimensionContentInterface {
            use DimensionContentTrait;

            /**
             * @return never
             */
            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            /**
             * @return never
             */
            public function getResource(): ContentRichEntityInterface
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }
        };
    }

    public function testGetSetLocale(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getLocale());
        $model->setLocale('de');
        $this->assertSame('de', $model->getLocale());
    }

    public function testGetSetGhostLocale(): void
    {
        $model = $this->getDimensionContentInstance();
        $model->setGhostLocale('de');
        $this->assertSame('de', $model->getGhostLocale());
    }

    public function testAddGetAvailableLocales(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->addAvailableLocale('en');
        $model->addAvailableLocale('de');
        $this->assertSame(['en', 'de'], $model->getAvailableLocales());
    }

    public function testAddSameAvailableLocale(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->addAvailableLocale('de');
        $model->addAvailableLocale('de');
        $this->assertSame(['de'], $model->getAvailableLocales());
    }

    public function testRemoveAvailableLocaleFirst(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->addAvailableLocale('de');
        $model->addAvailableLocale('en');
        $this->assertSame(['de', 'en'], $model->getAvailableLocales());
        $model->removeAvailableLocale('de');
        $this->assertSame(['en'], $model->getAvailableLocales());
    }

    public function testRemoveAvailableLocaleLast(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->addAvailableLocale('de');
        $model->addAvailableLocale('en');
        $this->assertSame(['de', 'en'], $model->getAvailableLocales());
        $model->removeAvailableLocale('en');
        $this->assertSame(['de'], $model->getAvailableLocales());
    }

    public function testRemoveAvailableLocaleNotSet(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->addAvailableLocale('en');
        $this->assertSame(['en'], $model->getAvailableLocales());
        $model->removeAvailableLocale('de');
        $this->assertSame(['en'], $model->getAvailableLocales());
    }

    public function testRemoveAvailableLocaleEmpty(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertNull($model->getAvailableLocales());
        $model->removeAvailableLocale('de');
        $this->assertNull($model->getAvailableLocales());
    }

    public function testGetSetStage(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertSame('draft', $model->getStage());
        $model->setStage('live');
        $this->assertSame('live', $model->getStage());
    }

    public function testGetDefaultAttributes(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertSame([
            'locale' => null,
            'stage' => 'draft',
        ], $model::getDefaultDimensionAttributes());
    }

    public function testGetSetIsMerged(): void
    {
        $model = $this->getDimensionContentInstance();
        $this->assertFalse($model->isMerged());

        $model->markAsMerged();
        $this->assertTrue($model->isMerged());
    }
}
