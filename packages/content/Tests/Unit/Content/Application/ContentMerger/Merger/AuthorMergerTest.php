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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\AuthorMerger;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class AuthorMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getAuthorMergerInstance(): MergerInterface
    {
        return new AuthorMerger();
    }

    public function testMergeSourceNotImplementAuthorInterface(): void
    {
        $merger = $this->getAuthorMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuthorInterface::class);
        $target->setAuthor(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementAuthorInterface(): void
    {
        $merger = $this->getAuthorMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuthorInterface::class);
        $source->getAuthor(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getAuthorMergerInstance();

        $contact = $this->prophesize(ContactInterface::class);
        $authoredDate = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuthorInterface::class);
        $source->getAuthor()->willReturn($contact->reveal())->shouldBeCalled();
        $source->getAuthored()->willReturn($authoredDate)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuthorInterface::class);
        $target->setAuthor($contact->reveal())->shouldBeCalled();
        $target->setAuthored($authoredDate)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getAuthorMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(AuthorInterface::class);
        $source->getAuthor()->willReturn(null)->shouldBeCalled();
        $source->getAuthored()->willReturn(null)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(AuthorInterface::class);
        $target->setAuthor(Argument::any())->shouldNotBeCalled();
        $target->setAuthored(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
