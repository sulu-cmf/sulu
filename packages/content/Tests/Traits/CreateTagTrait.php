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

namespace Sulu\Bundle\ContentBundle\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;

trait CreateTagTrait
{
    /**
     * @param array{name?: ?string} $data
     */
    protected static function createTag(array $data = []): Tag
    {
        $entityManager = static::getEntityManager();

        $tag = new Tag();
        $tag->setName($data['name'] ?? '');

        $entityManager->persist($tag);

        return $tag;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;
}
