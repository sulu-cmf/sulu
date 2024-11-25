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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Traits;

use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal
 */
trait ResolveContentTrait
{
    /**
     * @template E of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<E> $contentRichEntity
     *
     * @return E|null
     */
    protected function resolveContent(ContentRichEntityInterface $contentRichEntity, string $locale, bool $showDrafts = false): ?DimensionContentInterface
    {
        $stage = $showDrafts
            // TODO FIXME add testcase for it
            ? DimensionContentInterface::STAGE_DRAFT // @codeCoverageIgnore
            : DimensionContentInterface::STAGE_LIVE;

        try {
            $resolvedDimensionContent = $this->getContentManager()->resolve($contentRichEntity, [
                'locale' => $locale,
                'stage' => $stage,
            ]);
        } catch (ContentNotFoundException $exception) {
            return null;
        }

        if ($stage !== $resolvedDimensionContent->getStage() || $locale !== $resolvedDimensionContent->getLocale()) {
            return null;
        }

        return $resolvedDimensionContent;
    }

    abstract protected function getContentManager(): ContentManagerInterface;
}
