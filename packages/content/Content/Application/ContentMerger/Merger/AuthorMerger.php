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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class AuthorMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof AuthorInterface) {
            return;
        }

        if (!$sourceObject instanceof AuthorInterface) {
            return;
        }

        if ($author = $sourceObject->getAuthor()) {
            $targetObject->setAuthor($author);
        }

        if ($authored = $sourceObject->getAuthored()) {
            $targetObject->setAuthored($authored);
        }
    }
}
