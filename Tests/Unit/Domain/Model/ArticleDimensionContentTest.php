<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContent;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;

class ArticleDimensionContentTest extends TestCase
{
    /**
     * @var ObjectProphecy<ArticleInterface>
     */
    private $article;

    protected function setUp(): void
    {
        $this->article = $this->prophesize(ArticleInterface::class);
    }

    public function testGetResource(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $this->assertSame($this->article->reveal(), $articleDimensionContent->getResource());
    }

    public function testTitleTemplateData(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $articleDimensionContent->setTemplateData(['title' => 'My Title']);
        $this->assertSame('My Title', $articleDimensionContent->getTitle());
    }

    public function testTemplateData(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $articleDimensionContent->setTemplateData(['title' => 'My Title', 'description' => 'My Description']);
        $this->assertSame(
            [
                'title' => 'My Title',
                'description' => 'My Description',
            ],
            $articleDimensionContent->getTemplateData()
        );
    }

    public function testGetTemplateType(): void
    {
        $this->assertSame(
            // use string instead of constant to test regression which maybe need migrations
            'article',
            ArticleDimensionContent::getTemplateType()
        );
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(
            // use string instead of constant to test regression which maybe need migrations
            'articles',
            ArticleDimensionContent::getResourceKey()
        );
    }

    private function createArticlDimensionContent(): ArticleDimensionContentInterface
    {
        return new ArticleDimensionContent(
            $this->article->reveal()
        );
    }
}
