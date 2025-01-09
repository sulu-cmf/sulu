<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace vendor\sulu\sulu\src\Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Output\NullOutput;

class InitializerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private $initializer1;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private $initializer2;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private $initializer3;

    /**
     * @var Initializer
     */
    private $initializer;

    public function setUp(): void
    {
        $this->initializer1 = $this->prophesize(InitializerInterface::class);
        $this->initializer2 = $this->prophesize(InitializerInterface::class);
        $this->initializer3 = $this->prophesize(InitializerInterface::class);

        $this->initializer = new Initializer([
            'service1' => $this->initializer1->reveal(),
            'service3' => $this->initializer3->reveal(),
            'service2' => $this->initializer2->reveal(),
        ]);
    }

    public function testInitialize(): void
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service3';
        });

        $this->initializer->initialize();

        $this->assertEquals([
            'service1', 'service3', 'service2',
        ], $calls);
    }

    public function testInitializeWithPurge(): void
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service3';
        });

        $this->initializer->initialize(null, true);

        $this->assertEquals(['service1', 'service3', 'service2'], $calls);
    }
}
