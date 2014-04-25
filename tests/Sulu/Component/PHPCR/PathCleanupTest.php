<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

class PathCleanupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    protected function setUp()
    {
        $this->cleaner = new PathCleanup();
    }

    public function testCleanup()
    {
        $clean = $this->cleaner->cleanup('-/aSDf     asdf/äöü-', 'de');

        $this->assertEquals('/asdf-asdf/aeoeue', $clean);
    }

    public function testValidate()
    {
        $result = $this->cleaner->validate('-/aSDf     asdf/äöü-');
        $this->assertFalse($result);
        $result = $this->cleaner->validate('/asdf/asdf');
        $this->assertTrue($result);
        $result = $this->cleaner->validate('  ');
        $this->assertFalse($result);
    }
}
