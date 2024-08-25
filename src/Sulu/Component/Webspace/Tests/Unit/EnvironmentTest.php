<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Url;

class EnvironmentTest extends TestCase
{
    use ProphecyTrait;

    public function testToArray(): void
    {
        $expected = [
            'type' => 'test',
            'urls' => [
                0 => [
                    'url' => 'sulu.io',
                    'language' => null,
                    'country' => null,
                    'segment' => null,
                    'redirect' => null,
                    'main' => true,
                    'environment' => 'test',
                ],
            ],
        ];

        // Testing that the environment of the url is overridden when adding it to the environment object
        $url = new Url('sulu.io', 'I should be overwritten');

        $environment = new Environment($expected['type']);

        $environment->addUrl($url);

        $this->assertEquals($expected, $environment->toArray());
    }

    public static function addUrlProvider()
    {
        $urls = [
            // case 0
            self::getUrl(false),
            // case 1
            self::getUrl(true),
            // case 2
            self::getUrl(true),
            self::getUrl(false),
            // case 3
            self::getUrl(false),
            self::getUrl(true),
            // case 4
            self::getUrl(false),
            self::getUrl(true),
            self::getUrl(false),
        ];

        return [
            [[$urls[0]], $urls[0]],
            [[$urls[1]], $urls[1]],
            [[$urls[2], $urls[3]], $urls[2]],
            [[$urls[4], $urls[5]], $urls[5]],
            [[$urls[6], $urls[7], $urls[8]], $urls[7]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('addUrlProvider')]
    public function testAddUrl(array $urls, Url $expectedMainUrl): void
    {
        $environment = new Environment('prod');
        foreach ($urls as $url) {
            $environment->addUrl($url);
        }

        $this->assertEquals($urls, $environment->getUrls());
        $this->assertEquals($expectedMainUrl, $environment->getMainUrl());

        foreach ($environment->getUrls() as $url) {
            $this->assertEquals($url === $environment->getMainUrl(), $url->isMain());
        }
    }

    /**
     * Return url with given to-array result.
     *
     * @param bool $isMain
     *
     * @return Url
     */
    private static function getUrl($isMain = false)
    {
        $url = new Url('test', 'test');
        $url->setMain($isMain);

        return $url;
    }
}
