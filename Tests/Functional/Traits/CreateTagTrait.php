<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CreateTagTrait
{
    /**
     * @param array{
     *     name?: ?string,
     * } $data
     */
    public function createTag(array $data = []): TagInterface
    {
        $tagRepository = static::getContainer()->get('sulu.repository.tag');
        /** @var TagInterface $tag */
        $tag = $tagRepository->createNew();
        $tag->setName($data['name'] ?? '');

        static::getEntityManager()->persist($tag);

        return $tag;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;

    abstract protected static function getContainer(): ContainerInterface;
}
