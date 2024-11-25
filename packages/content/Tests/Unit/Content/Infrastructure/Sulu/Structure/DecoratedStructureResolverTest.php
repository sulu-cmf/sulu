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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\Structure;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentDocument;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\ContentStructureBridge;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure\DecoratedStructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\StructureInterface;

class DecoratedStructureResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureResolverInterface>
     */
    private $innerResolver;

    /**
     * @var DecoratedStructureResolver
     */
    private $decoratedResolver;

    protected function setUp(): void
    {
        $this->innerResolver = $this->prophesize(StructureResolverInterface::class);
        $this->decoratedResolver = new DecoratedStructureResolver($this->innerResolver->reveal());
    }

    public function testResolveWithNonContentStructureBridge(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $expectedData = ['key' => 'value'];

        $this->innerResolver->resolve($structure->reveal(), true)
            ->willReturn($expectedData);

        $result = $this->decoratedResolver->resolve($structure->reveal());

        $this->assertSame($expectedData, $result);
    }

    public function testResolveWithContentStructureBridgeNonAuthorInterface(): void
    {
        $structure = $this->prophesize(ContentStructureBridge::class);
        $document = $this->prophesize(ContentDocument::class);
        $content = $this->prophesize(TemplateInterface::class);

        $structure->getDocument()->willReturn($document->reveal());
        $document->getContent()->willReturn($content);

        $expectedData = ['key' => 'value'];
        $this->innerResolver->resolve($structure->reveal(), true)
            ->willReturn($expectedData);

        $result = $this->decoratedResolver->resolve($structure->reveal());

        $this->assertSame($expectedData, $result);
    }

    public function testResolveWithContentStructureBridgeAndAuthorInterface(): void
    {
        $structure = $this->prophesize(ContentStructureBridge::class);
        $document = $this->prophesize(ContentDocument::class);
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(AuthorInterface::class);
        $author = $this->prophesize(ContactInterface::class);

        $structure->getDocument()->willReturn($document->reveal());
        $document->getContent()->willReturn($content->reveal());

        $expectedData = ['key' => 'value'];
        $this->innerResolver->resolve($structure->reveal(), true)
            ->willReturn($expectedData);

        $authDate = new \DateTimeImmutable();
        $content->getAuthored()->willReturn($authDate);
        $content->getAuthor()->willReturn($author->reveal());
        $author->getId()->willReturn(123);

        $result = $this->decoratedResolver->resolve($structure->reveal());

        $expectedResult = \array_merge($expectedData, [
            'authored' => $authDate,
            'author' => 123,
        ]);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveWithContentStructureBridgeAndAuthorInterfaceNoAuthor(): void
    {
        $structure = $this->prophesize(ContentStructureBridge::class);
        $document = $this->prophesize(ContentDocument::class);
        $content = $this->prophesize(TemplateInterface::class);
        $content->willImplement(AuthorInterface::class);

        $structure->getDocument()->willReturn($document->reveal());
        $document->getContent()->willReturn($content->reveal());

        $expectedData = ['key' => 'value'];
        $this->innerResolver->resolve($structure->reveal(), true)
            ->willReturn($expectedData);

        $content->getAuthored()->willReturn(null);
        $content->getAuthor()->willReturn(null);

        $result = $this->decoratedResolver->resolve($structure->reveal());

        $expectedResult = \array_merge($expectedData, [
            'authored' => null,
            'author' => null,
        ]);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveWithExcerptFlag(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $expectedData = ['key' => 'value'];

        $this->innerResolver->resolve($structure->reveal(), false)
            ->willReturn($expectedData);

        $result = $this->decoratedResolver->resolve($structure->reveal(), false);

        $this->assertSame($expectedData, $result);
    }
}
