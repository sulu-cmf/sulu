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
use Sulu\Component\Webspace\Url;

class UrlTest extends TestCase
{
    public static function provideIsValidLocale()
    {
        return [
            ['de', 'at', 'de', 'at', true],
            ['de', '', 'de', '', true],
            ['de', null, 'de', null, true],
            [null, null, 'en', 'gb', true],
            ['en', null, 'de', null, false],
            ['en', 'us', 'en', 'gb', false],
            ['de', 'at', 'de', null, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIsValidLocale')]
    public function testIsValidLocale($urlLanguage, $urlCountry, $testLanguage, $testCountry, $result): void
    {
        $url = new Url();
        $url->setLanguage($urlLanguage);
        $url->setCountry($urlCountry);

        $this->assertEquals($result, $url->isValidLocale($testLanguage, $testCountry));
    }

    public function testToArray(): void
    {
        $url = new Url();

        $expected = [
            'language' => 'ello',
            'country' => 'as',
            'segment' => 'def',
            'redirect' => 'def',
            'url' => 'foo',
            'main' => true,
            'environment' => null,
        ];

        $url->setUrl($expected['url']);
        $url->setLanguage($expected['language']);
        $url->setCountry($expected['country']);
        $url->setSegment($expected['segment']);
        $url->setRedirect($expected['redirect']);
        $url->setMain($expected['main']);

        $this->assertEquals($expected, $url->toArray());
    }
}
