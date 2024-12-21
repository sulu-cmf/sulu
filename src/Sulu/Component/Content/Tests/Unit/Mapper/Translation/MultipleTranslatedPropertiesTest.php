<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Mapper\Translation;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

class MultipleTranslatedPropertiesTest extends TestCase
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    public function setUp(): void
    {
        $this->properties = new MultipleTranslatedProperties(
            [
                'template', 'foobar',
            ],
            'i18n'
        );
    }

    public static function provideGetName()
    {
        return [
            [Structure::TYPE_PAGE, 'foobar', 'i18n:de-foobar'],
            [Structure::TYPE_SNIPPET, 'template', 'template'],
            [Structure::TYPE_PAGE, 'template', 'i18n:de-template'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetName')]
    public function testGetName($structureType, $name, $expectedName): void
    {
        $this->properties->setLanguage('de');
        $this->properties->setStructureType($structureType);
        $res = $this->properties->getName($name);
        $this->assertEquals($expectedName, $res);
    }
}
