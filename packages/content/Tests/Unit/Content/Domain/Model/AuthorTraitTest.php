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
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorTrait;

class AuthorTraitTest extends TestCase
{
    use ProphecyTrait;

    protected function getAuthorInstance(): AuthorInterface
    {
        return new class() implements AuthorInterface {
            use AuthorTrait;
        };
    }

    public function testGetSetAuthor(): void
    {
        $model = $this->getAuthorInstance();
        $author = $this->prophesize(ContactInterface::class);
        $this->assertNull($model->getAuthor());
        $model->setAuthor($author->reveal());
        $this->assertSame($author->reveal(), $model->getAuthor());
    }

    public function testGetSetAuthored(): void
    {
        $model = $this->getAuthorInstance();
        $authored = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');
        $this->assertNull($model->getAuthored());
        $model->setAuthored($authored);
        $this->assertSame($authored, $model->getAuthored());
    }
}
