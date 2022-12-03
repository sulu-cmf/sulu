<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Domain\Model;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * @experimental
 *
 * @extends ContentRichEntityInterface<ArticleDimensionContentInterface>
 */
interface ArticleInterface extends AuditableInterface, ContentRichEntityInterface
{
    public const TEMPLATE_TYPE = 'article';
    public const RESOURCE_KEY = 'articles';

    /**
     * @internal
     */
    public function getId(): string;

    public function getUuid(): string;
}
