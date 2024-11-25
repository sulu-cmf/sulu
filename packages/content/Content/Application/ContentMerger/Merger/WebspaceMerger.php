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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceInterface;

class WebspaceMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof WebspaceInterface) {
            return;
        }

        if (!$sourceObject instanceof WebspaceInterface) {
            return;
        }

        if ($mainWebspace = $sourceObject->getMainWebspace()) {
            $targetObject->setMainWebspace($mainWebspace);
        }
    }
}
