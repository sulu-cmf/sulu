<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Slugifier;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Slugifier\NodeNameSlugifier;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class NodeNameSlugifierTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SlugifierInterface>
     */
    private $slugifier;

    /**
     * @var NodeNameSlugifier
     */
    private $nodeNameSlugifier;

    protected function setUp(): void
    {
        $this->slugifier = $this->prophesize(SlugifierInterface::class);
        $this->nodeNameSlugifier = new NodeNameSlugifier($this->slugifier->reveal());
    }

    public function testSlugify(): void
    {
        $this->slugifier->slugify('Test article')->willReturn('test-article');

        $this->assertEquals('test-article', $this->nodeNameSlugifier->slugify('Test article'));
    }

    public static function provide10eData()
    {
        return [
            ['10e', '10-e'],
            ['.10e', '.10-e'],
            ['-10e', '-10-e'],
            ['%10e', '%10-e'],
            ['test-10e-name', 'test-10-e-name'],
            ['test.10e-name', 'test.10-e-name'],
            ['test%10e-name', 'test%10-e-name'],
            ['test-10E-name', 'test-10-E-name'],
            ['test.10E-name', 'test.10-E-name'],
            ['test%10E-name', 'test%10-E-name'],
            ['test10E-name', 'test10-E-name'],
            ['test-9e-name', 'test-9-e-name'],
            ['test-500e-name', 'test-500-e-name'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provide10eData')]
    public function testSlugify10e($actual, $expected): void
    {
        $this->slugifier->slugify($actual)->willReturn($actual);

        $this->assertEquals($expected, $this->nodeNameSlugifier->slugify($actual));
    }
}
