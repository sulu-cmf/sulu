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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

interface ContentViewBuilderFactoryInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     *
     * @return array<string, ToolbarAction>
     */
    public function getDefaultToolbarActions(
        string $contentRichEntityClass
    ): array;

    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     * @param array<string, ToolbarAction> $toolbarActions
     *
     * @return ViewBuilderInterface[]
     */
    public function createViews(
        string $contentRichEntityClass,
        string $editParentView,
        ?string $addParentView = null,
        ?string $securityContext = null,
        ?array $toolbarActions = null
    ): array;
}
