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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * @experimental
 */
interface PageInterface extends AuditableInterface, ContentRichEntityInterface
{
    public const TEMPLATE_TYPE = 'page';
    public const RESOURCE_KEY = 'pages';

    /**
     * @internal
     */
    public function getId(): ?int;

    public function getUuid(): string;

    public function getWebspaceKey(): string;

    public function setWebspaceKey(string $webspaceKey): static;

    public function setParent(PageInterface $parent): static;
}
