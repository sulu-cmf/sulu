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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class TemplateMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof TemplateInterface) {
            return;
        }

        if (!$sourceObject instanceof TemplateInterface) {
            return;
        }

        if ($templateKey = $sourceObject->getTemplateKey()) {
            $targetObject->setTemplateKey($templateKey);
        }

        $targetObject->setTemplateData(\array_merge(
            $targetObject->getTemplateData(),
            $sourceObject->getTemplateData()
        ));
    }
}
