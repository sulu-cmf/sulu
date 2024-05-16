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

interface TemplateInterface
{
    public static function getTemplateType(): string;

    public function getTemplateKey(): ?string;

    public function setTemplateKey(string $templateKey): void;

    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(): array;

    /**
     * @param array<string, mixed> $templateData
     */
    public function setTemplateData(array $templateData): void;
}
