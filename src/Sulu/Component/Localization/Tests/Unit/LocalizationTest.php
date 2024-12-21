<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Localization\Localization;

class LocalizationTest extends TestCase
{
    /**
     * @var Localization
     */
    private $localization;

    public function setUp(): void
    {
        $this->localization = new Localization('de', 'at');
        $this->localization->setDefault(true);
        $this->localization->setXDefault(true);
    }

    public function testToArray(): void
    {
        $expected = [
            'language' => 'de',
            'localization' => 'de_at',
            'country' => 'at',
            'default' => true,
            'xDefault' => true,
            'children' => [],
            'shadow' => null,
        ];

        $this->assertEquals($expected, $this->localization->toArray());
    }

    public function testGetLocale(): void
    {
        $this->assertEquals('de_at', $this->localization->getLocale(Localization::UNDERSCORE));
        $this->assertEquals('de-at', $this->localization->getLocale(Localization::DASH));
        $this->assertEquals('de-AT', $this->localization->getLocale(Localization::ISO6391));
        $this->assertEquals('de_AT', $this->localization->getLocale(Localization::LCID));
    }

    public static function formatProvider()
    {
        return [
            [Localization::UNDERSCORE],
            [Localization::DASH],
            [Localization::ISO6391],
            [Localization::LCID],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formatProvider')]
    public function testCreateFromString($format): void
    {
        $localization = Localization::createFromString($format, $format);

        $this->assertEquals('de_at', $localization->getLocale());
    }
}
