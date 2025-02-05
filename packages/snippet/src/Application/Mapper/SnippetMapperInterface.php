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

use Sulu\Snippet\Domain\Model\SnippetInterface;

/**
 * @experimental
 */
interface SnippetMapperInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function mapSnippetData(SnippetInterface $snippet, array $data): void;
}
