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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\AuthorNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;

class AuthorNormalizerTest extends TestCase
{
    use ProphecyTrait;

    protected function createAuthorNormalizerInstance(): AuthorNormalizer
    {
        return new AuthorNormalizer();
    }

    public function testIgnoredAttributesNotImplementAuthorInterface(): void
    {
        $normalizer = $this->createAuthorNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createAuthorNormalizerInstance();
        $object = $this->prophesize(AuthorInterface::class);

        $this->assertSame(
            [
                'author',
            ],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementAuthorInterface(): void
    {
        $normalizer = $this->createAuthorNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'author' => 1,
            'authored' => new \DateTimeImmutable('2020-05-08T00:00:00+00:00'),
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhance(): void
    {
        $normalizer = $this->createAuthorNormalizerInstance();
        $object = $this->prophesize(AuthorInterface::class);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->shouldBeCalled()->willReturn(1);
        $object->getAuthor()->willReturn($contact->reveal());
        $authored = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');

        $data = [
            'author' => $contact->reveal(),
            'authored' => $authored,
        ];

        $expectedResult = [
            'author' => 1,
            'authored' => $authored,
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
