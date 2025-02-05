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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Structure;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentDocument;

class ContentDocumentTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentDocument(
        ?TemplateInterface $content = null,
        string $locale = 'en'
    ): ContentDocument {
        return new ContentDocument(
            $content ?: $this->prophesize(TemplateInterface::class)->reveal(), $locale
        );
    }

    public function testGetContent(): void
    {
        $content = $this->prophesize(TemplateInterface::class);

        $document = $this->createContentDocument($content->reveal());

        $this->assertSame($content->reveal(), $document->getContent());
    }

    public function testGetExtensionsData(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(SeoInterface::class);
        $content->willImplement(ExcerptInterface::class);

        $content->getSeoTitle()->willReturn('Seo Title');
        $content->getSeoDescription()->willReturn('Seo Description');
        $content->getSeoKeywords()->willReturn('Seo Keywords');
        $content->getSeoCanonicalUrl()->willReturn('http://sulu.io');
        $content->getSeoNoIndex()->willReturn(true);
        $content->getSeoNoFollow()->willReturn(true);
        $content->getSeoHideInSitemap()->willReturn(true);

        $content->getExcerptTitle()->willReturn('Excerpt Title');
        $content->getExcerptDescription()->willReturn('Excerpt Description');
        $content->getExcerptMore()->willReturn('Excerpt More');
        $content->getExcerptCategoryIds()->willReturn([4, 5, 6]);
        $content->getExcerptTagNames()->willReturn(['tag1', 'tag2', 'tag3']);
        $content->getExcerptImage()->willReturn(['id' => 42]);
        $content->getExcerptIcon()->willReturn(['id' => 43]);

        $document = $this->createContentDocument($content->reveal());

        $this->assertSame(
            [
                'seo' => [
                    'title' => 'Seo Title',
                    'description' => 'Seo Description',
                    'keywords' => 'Seo Keywords',
                    'canonicalUrl' => 'http://sulu.io',
                    'noIndex' => true,
                    'noFollow' => true,
                    'hideInSitemap' => true,
                ],
                'excerpt' => [
                    'title' => 'Excerpt Title',
                    'description' => 'Excerpt Description',
                    'more' => 'Excerpt More',
                    'categories' => [4, 5, 6],
                    'tags' => ['tag1', 'tag2', 'tag3'],
                    'images' => ['ids' => [42]],
                    'icon' => ['ids' => [43]],
                    'audience_targeting_groups' => [],
                ],
            ],
            $document->getExtensionsData()
        );
    }

    public function testSetExtensionsData(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $document = $this->createContentDocument();

        $document->setExtensionsData([]);
    }

    public function testSetExtension(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $document = $this->createContentDocument();

        $document->setExtension('excerpt', []);
    }

    public function testGetLocale(): void
    {
        $document = $this->createContentDocument();

        $this->assertSame('en', $document->getLocale());
    }

    public function testSetLocale(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $document = $this->createContentDocument();

        $document->setLocale('de');
    }

    public function testGetStructureType(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->getTemplateKey()->willReturn('default');

        $document = $this->createContentDocument($content->reveal());

        $this->assertSame('default', $document->getStructureType());
    }

    public function testSetStructureType(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $document = $this->createContentDocument();

        $document->setStructureType('default');
    }

    public function testSetOriginalLocale(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $document = $this->createContentDocument();

        $document->setOriginalLocale('de');
    }

    public function testGetOriginalLocale(): void
    {
        $document = $this->createContentDocument();

        $this->assertSame('en', $document->getOriginalLocale());
    }

    public function testGetStructure(): void
    {
        $document = $this->createContentDocument();

        /** @var StructureInterface|null $structure */
        $structure = $document->getStructure();

        $this->assertNull($structure);
    }

    public function testGetLastModified(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(AuthorInterface::class);
        $content->getLastModifiedEnabled()->willReturn(true);
        $authored = new \DateTimeImmutable('2020-01-01');
        $lastModified = new \DateTimeImmutable('2022-01-01');
        $content->getLastModified()->willReturn($lastModified);
        $content->getAuthored()->willReturn($authored);
        $document = $this->createContentDocument($content->reveal());

        $this->assertTrue($document->getLastModifiedEnabled());
        $this->assertSame($lastModified, $document->getLastModified());
        $this->assertSame($authored, $document->getAuthored());
    }

    public function testSetLastModified(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $lastModified = new \DateTimeImmutable('2020-01-01');
        $document = $this->createContentDocument();

        $document->setLastModified($lastModified);
    }

    public function testSetAuthored(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $authored = new \DateTime('2020-01-01');
        $document = $this->createContentDocument();

        $document->setAuthored($authored);
    }

    public function testSetAuthor(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $document = $this->createContentDocument();

        $document->setAuthor(null);
    }

    public function testGetAuthor(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(AuthorInterface::class);
        $author = new Contact();
        $content->getAuthor()->willReturn($author);
        $document = $this->createContentDocument($content->reveal());

        $this->assertSame($author, $document->getAuthor());
    }

    public function testGetUser(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(UserBlameInterface::class);
        $user = new User();
        $content->getCreator()->willReturn($user);
        $content->getChanger()->willReturn($user);
        $document = $this->createContentDocument($content->reveal());

        $this->assertSame($user, $document->getCreator());
        $this->assertSame($user, $document->getChanger());
    }

    public function testLocalizedAuthorNull(): void
    {
        $content = $this->prophesize(TemplateInterface::class);
        $document = $this->createContentDocument($content->reveal());

        $this->assertNull($document->getLastModifiedEnabled());
        $this->assertNull($document->getLastModified());
        $this->assertNull($document->getAuthored());
        $this->assertNull($document->getAuthor());
        $this->assertNull($document->getCreator());
        $this->assertNull($document->getChanger());
    }
}
