<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Symfony\Component\Uid\Uuid;

class ReferenceTest extends TestCase
{
    use SetGetPrivatePropertyTrait;

    public function testGetId(): void
    {
        $reference = $this->createReference();
        static::setPrivateProperty($reference, 'id', 1);
        static::assertSame(1, $reference->getId());
    }

    public function testGetSetResourceKey(): void
    {
        $reference = $this->createReference();
        $reference->setResourceKey('pages');
        static::assertSame('pages', $reference->getResourceKey());
    }

    public function testGetSetResourceId(): void
    {
        $reference = $this->createReference();
        $reference->setResourceId('123-123');
        static::assertSame('123-123', $reference->getResourceId());
    }

    public function testGetSetReferenceResourceKey(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceResourceKey('pages');
        static::assertSame('pages', $reference->getReferenceResourceKey());
    }

    public function testGetSetReferenceResourceId(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceResourceId('321-123');
        static::assertSame('321-123', $reference->getReferenceResourceId());
    }

    public function testGetSetReferenceTitle(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceTitle('Title');
        static::assertSame('Title', $reference->getReferenceTitle());
    }

    public function testGetSetReferenceLocale(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceLocale('de');
        static::assertSame('de', $reference->getReferenceLocale());
    }

    public function testGetSetReferenceRouterAttributes(): void
    {
        $reference = $this->createReference();
        static::assertSame([], $reference->getReferenceRouterAttributes());
        $reference->setReferenceRouterAttributes(['locale' => 'en']);
        static::assertSame(['locale' => 'en'], $reference->getReferenceRouterAttributes());
    }

    public function testGetSetReferenceProperty(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceProperty('id');
        static::assertSame('id', $reference->getReferenceProperty());
    }

    public function testGetSetReferenceContext(): void
    {
        $reference = $this->createReference();
        $reference->setReferenceContext('default');
        static::assertSame('default', $reference->getReferenceContext());
    }

    public function testEqualsTrue(): void
    {
        $uuid = Uuid::v7()->__toString();
        $reference1 = $this->createReference();
        $reference1->setResourceKey('media');
        $reference1->setResourceId('1');
        $reference1->setReferenceLocale('en');
        $reference1->setReferenceResourceKey('pages');
        $reference1->setReferenceResourceId($uuid);
        $reference1->setReferenceProperty('image');
        $reference1->setReferenceRouterAttributes(['webspace' => 'default']);
        $reference2 = $this->createReference();
        $reference2->setResourceKey('media');
        $reference2->setResourceId('1');
        $reference2->setReferenceLocale('en');
        $reference2->setReferenceResourceKey('pages');
        $reference2->setReferenceResourceId($uuid);
        $reference2->setReferenceProperty('image');
        $reference2->setReferenceRouterAttributes(['webspace' => 'default']);

        $this->assertTrue(
            $reference1->equals($reference2)
        );
    }

    public function testEqualsFalse(): void
    {
        $uuid = Uuid::v7()->__toString();
        $uuid2 = Uuid::v7()->__toString();
        $reference1 = $this->createReference();
        $reference1->setReferenceResourceId($uuid);
        $reference2 = $this->createReference();
        $reference2->setReferenceResourceId($uuid2);

        $this->assertFalse(
            $reference1->equals($reference2)
        );
    }

    private function createReference(): Reference
    {
        return new Reference();
    }
}
