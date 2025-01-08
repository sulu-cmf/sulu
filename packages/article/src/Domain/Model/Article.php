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

use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class Article implements ArticleInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<ArticleDimensionContentInterface>
     */
    use ContentRichEntityTrait;
    use AuditableTrait;

    /**
     * @var string
     */
    protected $uuid;

    public function __construct(
        ?string $uuid = null
    ) {
        $this->uuid = $uuid ?: Uuid::v7()->__toString();
    }

    public function getId(): string // TODO should be replaced by uuid
    {
        return $this->uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return ArticleDimensionContentInterface
     */
    public function createDimensionContent(): DimensionContentInterface
    {
        return new ArticleDimensionContent($this);
    }
}
