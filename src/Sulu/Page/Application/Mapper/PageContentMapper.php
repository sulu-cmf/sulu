<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Mapper;

use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Webmozart\Assert\Assert;

/**
 * @experimental
 *
 * @internal This class should be instantiated inside a project.
 *           Use the message to create or modify an Page.
 *           Or the inject all the mappers into a custom service.
 *           Create an own Mapper to extend the mapper with
 *           custom logic.
 */
final class PageContentMapper implements PageMapperInterface
{
    public function __construct(private ContentPersisterInterface $contentPersister)
    {
    }

    public function mapPageData(PageInterface $page, array $data): void
    {
        $locale = $data['locale'] ?? null;
        Assert::string($locale);

        $dimensionAttributes = ['locale' => $locale];

        // TODO this will be changed to `$page`, `$dimensionAttributes`, `$data`
        $this->contentPersister->persist($page, $data, $dimensionAttributes);
    }
}
