<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\SingleSignOn\Adapter\OpenId;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId\OpenIdSingleSignOnAdapter;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterProvider;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnLoginRequestSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SingleSignOnLoginRequestSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SingleSignOnAdapterProvider>
     */
    private $singleSignOnAdapterProvider;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy<UserRepository>
     */
    private $userRepository;

    private SingleSignOnLoginRequestSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->singleSignOnAdapterProvider = $this->prophesize(SingleSignOnAdapterProvider::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->userRepository = $this->prophesize(UserRepository::class);

        $this->subscriber = new SingleSignOnLoginRequestSubscriber(
            $this->singleSignOnAdapterProvider->reveal(),
            $this->urlGenerator->reveal(),
            $this->userRepository->reveal(),
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame([
            RequestEvent::class => [
                ['onKernelRequest', 9],
            ],
        ], SingleSignOnLoginRequestSubscriber::getSubscribedEvents());
    }

    public function testOnKernelRequestNotMainRequest(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_admin.login_check');
        $event = $this->createRequestEvent($request, HttpKernelInterface::SUB_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNotPost(): void
    {
        $request = Request::create('/admin/login');
        $event = $this->createRequestEvent($request);
        $request->attributes->set('_route', 'sulu_admin.login_check');

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNotLoginRoute(): void
    {
        $request = Request::create('/admin/login');
        $event = $this->createRequestEvent($request);
        $request->attributes->set('_route', 'sulu_admin.WRONG_NAME');

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestNoIdentifier(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $event = $this->createRequestEvent($request);
        $request->attributes->set('_route', 'sulu_admin.login_check');

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestWrongIdentifierAndNoUser(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_admin.login_check');
        $request->request->set('username', 'martin');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertSame('{"method":"json_login"}', $event->getResponse()?->getContent());
    }

    public function testOnKernelRequestUserNameAndPassword(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_admin.login_check');
        $request->request->set('username', 'martin');
        $request->request->set('password', '123');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestResetPasswordUser(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'martin');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestResetPasswordEmail(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'hello@sulu.io');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestResetPasswordExistingUser(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'admin');
        $event = $this->createRequestEvent($request);
        $user = new User();
        $user->setEmail('admin@sulu.io');

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestExistingUser(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'admin');
        $event = $this->createRequestEvent($request);
        $user = new User();
        $user->setEmail('admin@sulu.io');
        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestExistingUserAndPassword(): void
    {
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'admin');
        $request->request->set('password', 'admin');
        $event = $this->createRequestEvent($request);
        $user = new User();
        $user->setEmail('admin@sulu.io');
        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSsoEmail(): void
    {
        $redirectUrl = 'https://example.com/authorize';
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_admin.login_check');
        $request->request->set('username', 'martin@sulu.io');
        $event = $this->createRequestEvent($request);
        $openIdAdapter = $this->prophesize(OpenIdSingleSignOnAdapter::class);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('/admin');
        $this->singleSignOnAdapterProvider->getAdapterByDomain('sulu.io')->willReturn($openIdAdapter->reveal());
        $openIdAdapter->generateLoginUrl($request, '/admin', 'sulu.io')->willReturn($redirectUrl);

        $this->subscriber->onKernelRequest($event);

        $this->assertSame('{"method":"redirect","url":' . \json_encode($redirectUrl) . '}', $event->getResponse()?->getContent());
    }

    public function testOnKernelRequestSsoEmailPasswordReset(): void
    {
        $redirectUrl = 'https://example.com/authorize';
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'martin@sulu.io');
        $event = $this->createRequestEvent($request);
        $openIdAdapter = $this->prophesize(OpenIdSingleSignOnAdapter::class);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('/admin');
        $this->singleSignOnAdapterProvider->getAdapterByDomain('sulu.io')->willReturn($openIdAdapter->reveal());
        $openIdAdapter->generateLoginUrl($request, '/admin', 'sulu.io')->willReturn($redirectUrl);

        $this->subscriber->onKernelRequest($event);

        $this->assertSame('{"method":"redirect","url":' . \json_encode($redirectUrl) . '}', $event->getResponse()?->getContent());
    }

    public function testOnKernelRequestExistingSsoUser(): void
    {
        $redirectUrl = 'https://example.com/authorize';
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_admin.login_check');
        $request->request->set('username', 'admin');
        $event = $this->createRequestEvent($request);
        $openIdAdapter = $this->prophesize(OpenIdSingleSignOnAdapter::class);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('/admin');
        $this->singleSignOnAdapterProvider->getAdapterByDomain('sulu.io')->willReturn($openIdAdapter->reveal());
        $openIdAdapter->generateLoginUrl($request, '/admin', 'sulu.io')->willReturn($redirectUrl);
        $user = new User();
        $user->setEmail('admin@sulu.io');
        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $this->subscriber->onKernelRequest($event);

        $this->assertSame('{"method":"redirect","url":' . \json_encode($redirectUrl) . '}', $event->getResponse()?->getContent());
    }

    public function testOnKernelRequestExistingSsoUserResetPassword(): void
    {
        $redirectUrl = 'https://example.com/authorize';
        $request = Request::create('/admin/login', Request::METHOD_POST);
        $request->attributes->set('_route', 'sulu_security.reset_password.email');
        $request->request->set('username', 'admin');
        $event = $this->createRequestEvent($request);
        $openIdAdapter = $this->prophesize(OpenIdSingleSignOnAdapter::class);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('/admin');
        $this->singleSignOnAdapterProvider->getAdapterByDomain('sulu.io')->willReturn($openIdAdapter->reveal());
        $openIdAdapter->generateLoginUrl($request, '/admin', 'sulu.io')->willReturn($redirectUrl);
        $user = new User();
        $user->setEmail('admin@sulu.io');
        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $this->subscriber->onKernelRequest($event);

        $this->assertSame('{"method":"redirect","url":' . \json_encode($redirectUrl) . '}', $event->getResponse()?->getContent());
    }

    private function createRequestEvent(Request $request, ?int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $httpKernel = $this->prophesize(HttpKernelInterface::class);

        return new RequestEvent(
            $httpKernel->reveal(),
            $request,
            $requestType,
        );
    }
}
