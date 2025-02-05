<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Domain\Model;

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @experimental
 *
 * @extends ContentRichEntityInterface<SnippetDimensionContentInterface>
 */
interface SnippetInterface extends AuditableInterface, ContentRichEntityInterface
{
    public const TEMPLATE_TYPE = 'snippet';
    public const RESOURCE_KEY = 'snippets';

    /**
     * @internal
     */
    public function getId(): string;

    public function getUuid(): string;
}
