<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\NamespaceRegistry;

class NamespaceRegistryTest extends TestCase
{
    public function setUp(): void
    {
        $this->registry = new NamespaceRegistry([
            'system' => 'asys',
            'foobar' => 'lsys',
        ]);
    }

    /**
     * It should return an alias for a given role.
     */
    public function testGetPrefix()
    {
        $alias = $this->registry->getPrefix('system');
        $this->assertEquals('asys', $alias);
    }

    /**
     * It should thow an exception if the alias is not known.
     *
     * @expectedException \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testGetUnknownPrefix()
    {
        $this->registry->getPrefix('foobarbar');
    }
}
