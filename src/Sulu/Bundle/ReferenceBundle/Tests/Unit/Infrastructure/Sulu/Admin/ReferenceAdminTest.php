<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;

class ReferenceAdminTest extends TestCase
{
    private ReferenceAdmin $referenceAdmin;

    public function setUp(): void
    {
        $this->referenceAdmin = new ReferenceAdmin();
    }

    public function testGetSecurityContexts(): void
    {
        $this->assertEquals(
            [
                'Sulu' => [
                    'References' => [
                        'sulu.references.references' => [
                            'view',
                        ],
                    ],
                ],
            ],
            $this->referenceAdmin->getSecurityContexts()
        );
    }
}
