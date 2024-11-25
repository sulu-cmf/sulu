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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowTrait;

class ShadowTraitTest extends TestCase
{
    use ProphecyTrait;

    protected function getShadowInstance(): ShadowInterface
    {
        return new class() implements ShadowInterface {
            use ShadowTrait;
        };
    }

    public function testGetSetShadowLocale(): void
    {
        $model = $this->getShadowInstance();
        $this->assertNull($model->getShadowLocale());
        $model->setShadowLocale('en');
        $this->assertSame('en', $model->getShadowLocale());
    }

    public function testAddRemoveShadowLocales(): void
    {
        $model = $this->getShadowInstance();
        $this->assertNull($model->getShadowLocales());
        $model->removeShadowLocale('de');
        $this->assertNull($model->getShadowLocales());
        $model->addShadowLocale('de', 'en');
        $this->assertSame(['de' => 'en'], $model->getShadowLocales());
        $model->removeShadowLocale('de');
        $this->assertNull($model->getShadowLocales());
    }

    public function testGetShadowLocalesForLocale(): void
    {
        $model = $this->getShadowInstance();
        $this->assertSame([], $model->getShadowLocalesForLocale('en'));
        $model->addShadowLocale('de', 'en');
        $this->assertSame(['de'], $model->getShadowLocalesForLocale('en'));
    }
}
