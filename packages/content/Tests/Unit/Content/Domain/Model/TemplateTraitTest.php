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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateTrait;

class TemplateTraitTest extends TestCase
{
    protected function getTemplateInstance(): TemplateInterface
    {
        return new class() implements TemplateInterface {
            use TemplateTrait;

            public static function getTemplateType(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }
        };
    }

    public function testGetSetTemplateKey(): void
    {
        $model = $this->getTemplateInstance();
        $this->assertNull($model->getTemplateKey());
        $model->setTemplateKey('template');
        $this->assertSame('template', $model->getTemplateKey());
    }

    public function testGetSetTemplateData(): void
    {
        $model = $this->getTemplateInstance();
        $this->assertSame([], $model->getTemplateData());
        $model->setTemplateData(['data' => 'My Data']);
        $this->assertSame(['data' => 'My Data'], $model->getTemplateData());
    }
}
