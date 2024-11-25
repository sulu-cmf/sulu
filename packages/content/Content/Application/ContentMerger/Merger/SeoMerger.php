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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class SeoMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof SeoInterface) {
            return;
        }

        if (!$sourceObject instanceof SeoInterface) {
            return;
        }

        if ($seoTitle = $sourceObject->getSeoTitle()) {
            $targetObject->setSeoTitle($seoTitle);
        }

        if ($seoDescription = $sourceObject->getSeoDescription()) {
            $targetObject->setSeoDescription($seoDescription);
        }

        if ($seoKeywords = $sourceObject->getSeoKeywords()) {
            $targetObject->setSeoKeywords($seoKeywords);
        }

        if ($seoCanonicalUrl = $sourceObject->getSeoCanonicalUrl()) {
            $targetObject->setSeoCanonicalUrl($seoCanonicalUrl);
        }

        if ($seoNoIndex = $sourceObject->getSeoNoIndex()) {
            $targetObject->setSeoNoIndex($seoNoIndex);
        }

        if ($seoNoFollow = $sourceObject->getSeoNoFollow()) {
            $targetObject->setSeoNoFollow($seoNoFollow);
        }

        if ($seoHideInSitemap = $sourceObject->getSeoHideInSitemap()) {
            $targetObject->setSeoHideInSitemap($seoHideInSitemap);
        }
    }
}
