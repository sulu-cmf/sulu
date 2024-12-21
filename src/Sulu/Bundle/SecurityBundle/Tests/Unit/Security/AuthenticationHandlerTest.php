<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class AuthenticationHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AuthenticationHandler
     */
    private $authenticationHandler;

    /**
     * @var ObjectProphecy<AuthenticationException>
     */
    private $exception;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    private $token;

    /**
     * @var ObjectProphecy<UserInterface>
     */
    private $user;

    public function setUp(): void
    {
        $this->exception = $this->prophesize(AuthenticationException::class);
        $this->exception->getMessageKey()->willReturn('error');
        $this->request = $this->prophesize(Request::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);

        $this->token->getUser()->willReturn($this->user->reveal());

        $router = $this->prophesize(RouterInterface::class);
        $session = $this->prophesize(Session::class);
        $session->get('_security.admin.target_path')->willReturn('/admin/#target/path');
        $session->set(
            \class_exists(SecurityRequestAttributes::class)
                ? SecurityRequestAttributes::AUTHENTICATION_ERROR
                : (\class_exists(Security::class)
                    ? Security::AUTHENTICATION_ERROR // BC layer to Symfony <=6.4
                    : SymfonyCoreSecurity::AUTHENTICATION_ERROR), // BC layer to Symfony <=5.4
            $this->exception->reveal()
        )->will(function() {});
        $this->request->getSession()
            ->willReturn($session->reveal());
        $router->generate('sulu_admin')->willReturn('/admin');
        $router->generate('sulu_admin')->willReturn('/admin');

        $this->authenticationHandler = new AuthenticationHandler($router->reveal(), ['email', 'trusted_devices']);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $this->request->isXmlHttpRequest()->willReturn(false);

        $response = $this->authenticationHandler->onAuthenticationSuccess(
            $this->request->reveal(),
            $this->token->reveal()
        );

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationSuccessAjax(): void
    {
        $this->request->isXmlHttpRequest()->willReturn(true);

        $this->token->getUserIdentifier()->willReturn('testuser');

        $response = $this->authenticationHandler->onAuthenticationSuccess(
            $this->request->reveal(),
            $this->token->reveal()
        );

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(200, $response->getStatusCode());

        $response = \json_decode($response->getContent(), true);
        $this->assertSame([
            'url' => '/admin/#target/path',
            'username' => 'testuser',
            'completed' => true,
            'twoFactorMethods' => ['trusted_devices'],
        ], $response);
    }

    public function testOnAuthenticationFailure(): void
    {
        $this->request->isXmlHttpRequest()->willReturn(false);

        $response = $this->authenticationHandler->onAuthenticationFailure(
            $this->request->reveal(),
            $this->exception->reveal()
        );

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationFailureAjax(): void
    {
        $this->request->isXmlHttpRequest()->willReturn(true);

        $response = $this->authenticationHandler->onAuthenticationFailure(
            $this->request->reveal(),
            $this->exception->reveal()
        );

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
