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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Mocks;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

/**
 * Trait for composing a class that wraps a TemplateInterface mock.
 *
 * @see MockWrapper to learn why this trait is needed.
 *
 * @property TemplateInterface $instance
 */
trait TemplateMockWrapperTrait
{
    public static function getTemplateType(): string
    {
        return 'mock-template-type';
    }

    public function getTemplateKey(): ?string
    {
        return $this->instance->getTemplateKey();
    }

    public function setTemplateKey(string $templateKey): void
    {
        $this->instance->setTemplateKey($templateKey);
    }

    public function getTemplateData(): array
    {
        return $this->instance->getTemplateData();
    }

    public function setTemplateData(array $templateData): void
    {
        $this->instance->setTemplateData($templateData);
    }
}
