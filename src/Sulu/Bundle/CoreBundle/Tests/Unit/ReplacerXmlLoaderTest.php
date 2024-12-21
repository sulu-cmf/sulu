<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CoreBundle\DataFixtures\ReplacerXmlLoader;
use Symfony\Component\Config\FileLocatorInterface;

class ReplacerXmlLoaderTest extends TestCase
{
    use ProphecyTrait;

    public function testLoad(): void
    {
        $filename = 'replacers.xml';

        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate($filename)->willReturn(__DIR__ . '/' . $filename);

        $loader = new ReplacerXmlLoader($locator->reveal());
        $result = $loader->load($filename);

        $this->assertEquals(
            [
                'de' => [
                    'ü' => 'ue',
                    '&' => 'und',
                ],
                'en' => [
                    '&' => 'and',
                ],
                'default' => [
                    '.' => '-',
                    '+' => '-',
                    ' ' => '-',
                ],
            ],
            $result
        );
    }

    public static function examplesDataProvider()
    {
        return [
            ['default', ' ', '-'],
            ['default', '.', '-'],
            ['default', '+', '-'],
            ['default', '~', '-'],
            ['default', '^', '-'],
            ['default', 'ä', 'ae'],
            ['default', 'ö', 'oe'],
            ['default', 'ü', 'ue'],
            ['default', 'Ä', 'Ae'],
            ['default', 'Ö', 'Oe'],
            ['default', 'Ü', 'Ue'],
            ['en', '&', 'and'],
            ['de', '&', 'und'],
            ['fr', '&', 'et'],
            ['it', '&', 'e'],
            ['nl', '&', 'en'],
            ['es', '&', 'y'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('examplesDataProvider')]
    public function testRealFile($locale, $from, $to): void
    {
        $filename = 'replacers.xml';

        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate($filename)->willReturn(__DIR__ . '/../../DataFixtures/' . $filename);

        $loader = new ReplacerXmlLoader($locator->reveal());
        $result = $loader->load($filename);

        $this->assertEquals($to, $result[$locale][$from]);
    }
}
