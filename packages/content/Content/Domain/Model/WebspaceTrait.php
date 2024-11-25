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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Model;

trait WebspaceTrait
{
    /**
     * @var string|null
     */
    protected $mainWebspace;

    public function getMainWebspace(): ?string
    {
        return $this->mainWebspace;
    }

    public function setMainWebspace(?string $mainWebspace): void
    {
        $this->mainWebspace = $mainWebspace;
    }
}
