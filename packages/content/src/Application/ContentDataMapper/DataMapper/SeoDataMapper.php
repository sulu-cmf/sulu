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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Webmozart\Assert\Assert;

class SeoDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof SeoInterface) {
            return;
        }

        $this->setSeoData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setSeoData(SeoInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('seoTitle', $data)) {
            Assert::nullOrString($data['seoTitle']);
            $dimensionContent->setSeoTitle($data['seoTitle']);
        }

        if (\array_key_exists('seoDescription', $data)) {
            Assert::nullOrString($data['seoDescription']);
            $dimensionContent->setSeoDescription($data['seoDescription']);
        }

        if (\array_key_exists('seoKeywords', $data)) {
            Assert::nullOrString($data['seoKeywords']);
            $dimensionContent->setSeoKeywords($data['seoKeywords']);
        }

        if (\array_key_exists('seoCanonicalUrl', $data)) {
            Assert::nullOrString($data['seoCanonicalUrl']);
            $dimensionContent->setSeoCanonicalUrl($data['seoCanonicalUrl']);
        }

        if (\array_key_exists('seoHideInSitemap', $data)) {
            Assert::boolean($data['seoHideInSitemap']);
            $dimensionContent->setSeoHideInSitemap($data['seoHideInSitemap']);
        }

        if (\array_key_exists('seoNoFollow', $data)) {
            Assert::boolean($data['seoNoFollow']);
            $dimensionContent->setSeoNoFollow($data['seoNoFollow']);
        }

        if (\array_key_exists('seoNoIndex', $data)) {
            Assert::boolean($data['seoNoIndex']);
            $dimensionContent->setSeoNoIndex($data['seoNoIndex']);
        }
    }
}
