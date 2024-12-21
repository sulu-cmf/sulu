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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId\OpenIdSingleSignOnAdapter;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterProvider;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnTokenExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class SingleSignOnTokeExtractorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SingleSignOnAdapterProvider>
     */
    private $singleSignOnAdapterProvider;

    private SingleSignOnTokenExtractor $tokenExtractor;

    protected function setUp(): void
    {
        if (!\interface_exists(AccessTokenExtractorInterface::class)) {
            $this->markTestSkipped('This test requires symfony/security-http ^6.2');
        }

        $this->singleSignOnAdapterProvider = $this->prophesize(SingleSignOnAdapterProvider::class);

        $this->tokenExtractor = new SingleSignOnTokenExtractor(
            $this->singleSignOnAdapterProvider->reveal(),
        );
    }

    public function testOnKernelRequestExistingSsoUser(): void
    {
        $request = Request::create('/admin', Request::METHOD_GET);
        $session = new Session(new MockArraySessionStorage());
        $session->set(OpenIdSingleSignOnAdapter::OPEN_ID_ATTRIBUTES, [
            'state' => '3cc05262-d86b-4b93-9456-5594ae8f3ed0',
            'domain' => 'sulu.io',
        ]);
        $request->setSession($session);
        $request->attributes->set('_route', 'sulu_admin');
        $request->query->set('code', '4/0AeaYSHCvSWVukKJ-rueX8WyKj-ycUM');
        $request->query->set('state', '3cc05262-d86b-4b93-9456-5594ae8f3ed0');
        $adapter = $this->prophesize(OpenIdSingleSignOnAdapter::class);
        $adapter->isAuthorizationValid(Argument::any(), Argument::any())->willReturn(true);
        $this->singleSignOnAdapterProvider->getAdapterByDomain('sulu.io')->willReturn($adapter->reveal());

        $accessToken = $this->tokenExtractor->extractAccessToken($request);
        $this->assertSame('sulu.io::4/0AeaYSHCvSWVukKJ-rueX8WyKj-ycUM', $accessToken);
    }
}
