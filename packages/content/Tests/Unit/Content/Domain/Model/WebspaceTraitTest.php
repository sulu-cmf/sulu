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
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceTrait;

class WebspaceTraitTest extends TestCase
{
    protected function getWebspaceInstance(): WebspaceInterface
    {
        return new class() implements WebspaceInterface {
            use WebspaceTrait;
        };
    }

    public function testGetSetMainWebspace(): void
    {
        $model = $this->getWebspaceInstance();
        $this->assertNull($model->getMainWebspace());
        $model->setMainWebspace('example');
        $this->assertSame('example', $model->getMainWebspace());
    }
}
