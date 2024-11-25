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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Doctrine;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TagFactoryTest extends SuluTestCase
{
    /**
     * @var TagFactoryInterface
     */
    private $tagFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();

        $this->tagFactory = self::getContainer()->get('sulu_content.tag_factory');
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string[] $tagNames
     * @param string[] $existTags
     */
    public function testCreate(array $tagNames, array $existTags): void
    {
        $this->createTags($existTags);

        $tags = $this->tagFactory->create($tagNames);

        $this->assertSame(
            $tagNames,
            \array_map(
                function(TagInterface $tag) {
                    return $tag->getName();
                },
                $tags
            )
        );
    }

    public function testCreateSameTagTwice(): void
    {
        $tags1 = $this->tagFactory->create(['Tag 1']);
        $tags2 = $this->tagFactory->create(['Tag 1']);

        $this->assertSame($tags1, $tags2);

        $this->getEntityManager()->flush();
    }

    public function testCreateSameTagTwiceWithOtherEntityInUnitOfWork(): void
    {
        $this->getEntityManager()->persist($this->createOtherEntity());

        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = self::getContainer()->get('sulu.repository.tag');
        $tag = $tagRepository->createNew();
        $tag->setName('Other Tag');
        $this->getEntityManager()->persist($tag);

        $tags1 = $this->tagFactory->create(['Tag 1']);
        $tags2 = $this->tagFactory->create(['Tag 1']);

        $this->assertSame($tags1, $tags2);

        $this->getEntityManager()->flush();
    }

    /**
     * @return \Generator<mixed[]>
     */
    public function dataProvider(): \Generator
    {
        yield [
            [
                // No tags
            ],
            [
                // No exist tags
            ],
        ];

        yield [
            [
                'Tag 1',
                'Tag 2',
            ],
            [
                // No exist tags
            ],
        ];

        yield [
            [
                'Exist Tag 1',
                'Tag 2',
            ],
            [
                'Exist Tag 1',
                'Other Exist 3',
            ],
        ];

        yield [
            [
                'Exist Tag 1',
                'Exist Tag 2',
            ],
            [
                'Exist Tag 1',
                'Exist Tag 2',
            ],
        ];
    }

    /**
     * @param string[] $existTagNames
     */
    private function createTags(array $existTagNames = []): void
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = self::getContainer()->get('sulu.repository.tag');

        foreach ($existTagNames as $existTagName) {
            $existTag = $tagRepository->createNew();
            $existTag->setName($existTagName);
            self::getEntityManager()->persist($existTag);
        }

        if (\count($existTagNames)) {
            self::getEntityManager()->flush();
            self::getEntityManager()->clear();
        }
    }

    private function createOtherEntity(): object
    {
        $contact = new Contact();
        $contact->setFirstName('Dummy');
        $contact->setLastName('Entity');

        return $contact;
    }
}
