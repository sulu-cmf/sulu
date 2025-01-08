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

namespace Sulu\Content\Domain\Model;

/**
 * Basic implementation of the TemplateInterface.
 */
trait TemplateTrait
{
    /**
     * @var string|null
     */
    private $templateKey;

    /**
     * @var array<string, mixed>
     */
    private $templateData = [];

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(?string $templateKey): void
    {
        $this->templateKey = $templateKey;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @param array<string, mixed> $templateData
     */
    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }
}
