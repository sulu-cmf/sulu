<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

class SnippetResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<SnippetRepositoryInterface>
     */
    private ObjectProphecy $snippetRepository;

    private SnippetResourceLoader $loader;

    public function setUp(): void
    {
        $this->snippetRepository = $this->prophesize(SnippetRepositoryInterface::class);
        $this->loader = new SnippetResourceLoader($this->snippetRepository->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('snippet', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $snippet1 = $this->createSnippet(1);
        $snippet2 = $this->createSnippet(3);

        $this->snippetRepository->findBy(['id' => [1, 3]])->willReturn([
            $snippet1,
            $snippet2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en');

        $this->assertSame([
            1 => $snippet1,
            3 => $snippet2,
        ], $result);
    }

    private static function createSnippet(int $id): Snippet
    {
        $snippet = new Snippet();
        self::setprivateProperty($snippet, 'uuid', $id);

        return $snippet;
    }
}
