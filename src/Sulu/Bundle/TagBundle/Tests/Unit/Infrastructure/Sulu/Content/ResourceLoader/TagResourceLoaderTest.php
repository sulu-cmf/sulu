<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\ResourceLoader\TagResourceLoader;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class TagResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TagRepositoryInterface>
     */
    private ObjectProphecy $tagRepository;

    private TagResourceLoader $loader;

    public function setUp(): void
    {
        $this->tagRepository = $this->prophesize(TagRepositoryInterface::class);
        $this->loader = new TagResourceLoader($this->tagRepository->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('tag', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $tag1 = $this->createTag(1);
        $tag2 = $this->createTag(3);

        $this->tagRepository->findBy(['name' => ['Tag 1', 'Tag 3']])->willReturn([
            $tag1,
            $tag2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load(['Tag 1', 'Tag 3'], 'en', []);

        $this->assertSame([
            'Tag 1' => $tag1->getName(),
            'Tag 3' => $tag2->getName(),
        ], $result);
    }

    private static function createTag(int $id): Tag
    {
        $tag = new Tag();
        static::setPrivateProperty($tag, 'id', $id);
        $tag->setName('Tag ' . $id);

        return $tag;
    }
}
