<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\ClassNameInflector;

class ClassNameInflectorTest extends TestCase
{
    public function testInflector()
    {
        $this->assertEquals(
            'Hello',
            ClassNameInflector::getUserClassName('Hello')
        );
    }
}
