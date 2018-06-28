<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use PHPUnit\Framework\TestCase;

class AuthenticationEntryPointTest extends TestCase
{
    /**
     * @var AuthenticationEntryPoint
     */
    private $authenticationEntryPoint;

    public function setUp()
    {
        parent::setUp();

        $urlGenerator = $this->prophesize('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $urlGenerator->generate('sulu_admin.login')->willReturn('/admin/login');
        $this->authenticationEntryPoint = new AuthenticationEntryPoint($urlGenerator->reveal());
    }

    public function testStart()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals(401, $result->getStatusCode());
    }
}
