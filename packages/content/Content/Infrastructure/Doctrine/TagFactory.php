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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class TagFactory implements TagFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<TagInterface>
     */
    private $tagRepository;

    /**
     * @param EntityRepository<TagInterface> $tagRepository
     */
    public function __construct(EntityManagerInterface $entityManager, EntityRepository $tagRepository)
    {
        $this->entityManager = $entityManager;
        $this->tagRepository = $tagRepository;
    }

    public function create(array $tagNames): array
    {
        if (empty($tagNames)) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(TagInterface::class, 'tag')
            ->select('tag');
        $queryBuilder->where($queryBuilder->expr()->in('tag.name', $tagNames));
        /** @var iterable<TagInterface> $tags */
        $tags = $queryBuilder->getQuery()->getResult();

        // sort tags by the given names order
        $excerptTags = [];
        foreach ($tags as $tag) {
            $index = \array_search($tag->getName(), $tagNames, true);
            $excerptTags[$index] = $tag;
            unset($tagNames[$index]);
        }

        // check if a tag with the same name was yet persisted and use that instead of create one
        // this avoids a unique constraint error to create multiple tag with same name
        if (\count($tagNames)) {
            // we use here the unitOfWork instead of an own cache this avoid us listing for
            // flush, clear or deletion events and so we don't need to invalid an cache ourselves
            foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $object) {
                if (!$object instanceof TagInterface) {
                    continue;
                }

                $index = \array_search($object->getName(), $tagNames, true);

                if (false === $index) {
                    continue;
                }

                $excerptTags[$index] = $object;
                unset($tagNames[$index]);
            }
        }

        // create missing tags which not exist yet
        foreach ($tagNames as $index => $tagName) {
            /** @var TagInterface $tag */
            $tag = $this->tagRepository->createNew();
            $tag->setName($tagName);

            $this->entityManager->persist($tag);

            $excerptTags[$index] = $tag;
        }

        return $excerptTags;
    }
}
