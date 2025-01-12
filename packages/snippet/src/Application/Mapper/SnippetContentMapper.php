<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\Mapper;

use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Webmozart\Assert\Assert;

/**
 * @experimental
 *
 * @internal This class should be instantiated inside a project.
 *           Use the message to create or modify an snippet.
 *           Or inject all the mappers into a custom service.
 *           Create an own Mapper to extend the mapper with
 *           custom logic.
 */
final class SnippetContentMapper implements SnippetMapperInterface
{
    /**
     * @var ContentPersisterInterface
     */
    private $contentPersister;

    public function __construct(ContentPersisterInterface $contentPersister)
    {
        $this->contentPersister = $contentPersister;
    }

    public function mapSnippetData(SnippetInterface $snippet, array $data): void
    {
        $locale = $data['locale'] ?? null;
        Assert::string($locale);

        $dimensionAttributes = ['locale' => $locale];

        // TODO this will be changed to `$snippet`, `$dimensionAttributes`, `$data`
        $this->contentPersister->persist($snippet, $data, $dimensionAttributes);
    }
}
