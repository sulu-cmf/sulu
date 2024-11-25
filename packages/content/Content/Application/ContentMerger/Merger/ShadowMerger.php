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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class ShadowMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof ShadowInterface) {
            return;
        }

        if (!$sourceObject instanceof ShadowInterface) {
            return;
        }

        if ($shadowLocale = $sourceObject->getShadowLocale()) {
            $targetObject->setShadowLocale($shadowLocale);
        }

        foreach (($sourceObject->getShadowLocales() ?? []) as $locale => $shadowLocale) {
            $targetObject->addShadowLocale($locale, $shadowLocale);
        }
    }
}
