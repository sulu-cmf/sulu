<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Model;

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class Page implements PageInterface
{
    use AuditableTrait;
    use ContentRichEntityTrait;

    private ?int $id = null;

    private string $uuid;

    private string $webspaceKey;

    private int $lft;

    private int $rgt;

    private int $depth;

    private PageInterface $parent;

    private Collection $children;

    public function __construct(
        ?string $uuid = null
    ) {
        $this->initializeDimensionContents();
        $this->uuid = $uuid ?: Uuid::v7()->toRfc4122();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function createDimensionContent(): DimensionContentInterface
    {
        return new PageDimensionContent($this);
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey(string $webspaceKey): static
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function setParent(PageInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
