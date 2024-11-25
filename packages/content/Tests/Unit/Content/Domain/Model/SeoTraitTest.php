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
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoTrait;

class SeoTraitTest extends TestCase
{
    protected function getSeoInstance(): SeoInterface
    {
        return new class() implements SeoInterface {
            use SeoTrait;
        };
    }

    public function testGetSetSeoTitle(): void
    {
        $model = $this->getSeoInstance();
        $this->assertNull($model->getSeoTitle());
        $model->setSeoTitle('Seo Title');
        $this->assertSame('Seo Title', $model->getSeoTitle());
    }

    public function testGetSetSeoDescription(): void
    {
        $model = $this->getSeoInstance();
        $this->assertNull($model->getSeoDescription());
        $model->setSeoDescription('Seo Description');
        $this->assertSame('Seo Description', $model->getSeoDescription());
    }

    public function testGetSetSeoKeywords(): void
    {
        $model = $this->getSeoInstance();
        $this->assertNull($model->getSeoKeywords());
        $model->setSeoKeywords('Keyword 1, Keyword 2');
        $this->assertSame('Keyword 1, Keyword 2', $model->getSeoKeywords());
    }

    public function testGetSetSeoCanonicalUrl(): void
    {
        $model = $this->getSeoInstance();
        $this->assertNull($model->getSeoCanonicalUrl());
        $model->setSeoCanonicalUrl('/test-page');
        $this->assertSame('/test-page', $model->getSeoCanonicalUrl());
    }

    public function testGetSetSeoNoFollow(): void
    {
        $model = $this->getSeoInstance();
        $this->assertFalse($model->getSeoNoFollow());
        $model->setSeoNoFollow(true);
        $this->assertTrue($model->getSeoNoFollow());
    }

    public function testGetSetSeoNoIndex(): void
    {
        $model = $this->getSeoInstance();
        $this->assertFalse($model->getSeoNoIndex());
        $model->setSeoNoIndex(true);
        $this->assertTrue($model->getSeoNoIndex());
    }

    public function testGetSetSeoHideInSitemap(): void
    {
        $model = $this->getSeoInstance();
        $this->assertFalse($model->getSeoHideInSitemap());
        $model->setSeoHideInSitemap(true);
        $this->assertTrue($model->getSeoHideInSitemap());
    }
}
