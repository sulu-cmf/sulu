<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Tests;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Hash\RequestHashChecker;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Symfony\Component\HttpFoundation\Request;

class RequestHashCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<HasherInterface>
     */
    private $hasher;

    /**
     * @var RequestHashChecker
     */
    private $requestHashChecker;

    public function setUp(): void
    {
        $this->hasher = $this->prophesize(HasherInterface::class);
        $this->requestHashChecker = new RequestHashChecker($this->hasher->reveal());
    }

    public static function provideCheckHash()
    {
        return [
            ['false', 'hash', 'hash', true],
            ['false', 'wrong-hash', 'hash', false],
            ['false', null, 'hash', true],
            ['true', 'wrong-hash', 'hash', true],
            ['true', 'hash', 'hash', true],
            [null, 'hash', 'hash', true],
            [null, 'wrong-hash', 'hash', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideCheckHash')]
    public function testCheckHash($force, $givenHash, $realHash, $valid): void
    {
        $request = new Request(['force' => $force], $givenHash ? ['_hash' => $givenHash] : []);
        $object = new \stdClass();

        $this->hasher->hash($object)->willReturn($realHash);

        if (!$valid) {
            $this->expectException(InvalidHashException::class);
        }

        $result = $this->requestHashChecker->checkHash($request, $object, 1);

        if ($valid) {
            $this->assertEquals($valid, $result);
        }
    }
}
