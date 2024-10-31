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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId\OpenIdSingleSignOnAdapter;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class OpenIdSingleSignOnAdapterTest extends TestCase
{
    use ProphecyTrait;

    private MockHttpClient $httpClient;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ContactRepositoryInterface>
     */
    private $contactRepository;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    private OpenIdSingleSignOnAdapter $adapter;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);

        $this->adapter = new OpenIdSingleSignOnAdapter(
            $this->httpClient,
            $this->userRepository->reveal(),
            $this->entityManager->reveal(),
            $this->contactRepository->reveal(),
            $this->roleRepository->reveal(),
            $this->urlGenerator->reveal(),
            'https://example.com/endpoint',
            'clientId',
            'clientSecret',
            'userRole',
            ['de', 'en'],
        );
    }

    public function testGenerateLoginUrl(): void
    {
        $session = new Session(new MockArraySessionStorage());
        /** @var string $responseContent */
        $responseContent = \json_encode([
            'authorization_endpoint' => 'https://example.com/authorize',
        ]);

        $response = new MockResponse($responseContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->httpClient->setResponseFactory([$response]);
        $request = new Request();
        $request->setSession($session);
        $redirectUrl = 'https://example.com/redirect';
        $domain = 'example.com';

        $loginUrl = $this->adapter->generateLoginUrl($request, $redirectUrl, $domain);

        $this->assertStringStartsWith('https://example.com/authorize', $loginUrl);

        /** @var array{
         *     domain: string,
         *     state: string,
         * } $openIdAttributes
         */
        $openIdAttributes = $session->get(OpenIdSingleSignOnAdapter::OPEN_ID_ATTRIBUTES);
        $this->assertSame($domain, $openIdAttributes['domain']);
        $this->assertIsString($openIdAttributes['state']);
    }

    public function testIsAuthorizationValid(): void
    {
        $isValid = $this->adapter->isAuthorizationValid(['state' => 'f20f9604-7577-4ac8-8890-9a6fbf359259'], ['state' => 'f20f9604-7577-4ac8-8890-9a6fbf359259']);
        $isNotValid = $this->adapter->isAuthorizationValid(['state' => 'f20f9604-7577-4ac8-8890-9a6fbf359259'], ['state' => '123-7577-4ac8-8890-9a6fbf359259']);
        $isNotSet = $this->adapter->isAuthorizationValid([], ['state' => '123-7577-4ac8-8890-9a6fbf359259']);

        $this->assertTrue($isValid);
        $this->assertFalse($isNotValid);
        $this->assertFalse($isNotSet);
    }

    public function testCreateOrUpdateUser(): void
    {
        $token = 'mock_token';
        $expectedRequests = [
            function($method, $url, $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://example.com/endpoint', $url);

                /** @var string $response */
                $response = \json_encode([
                    'token_endpoint' => 'https://token_endpoint.com',
                    'userinfo_endpoint' => 'https://userinfo_endpoint.com',
                ]);

                return new MockResponse($response);
            },

            function($method, $url, $options): MockResponse {
                $this->assertSame('POST', $method);
                $this->assertSame('https://token_endpoint.com/', $url);

                /** @var string $response */
                $response = \json_encode([
                    'access_token' => 'ya29.a0Ad52N38l9acjoNc975Apn4W4H8DK_TtX_S',
                ]);

                return new MockResponse($response);
            },

            function($method, $url, $options): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertSame('https://userinfo_endpoint.com/', $url);

                /** @var string $response */
                $response = \json_encode([
                    'email' => 'hello@sulu.io',
                    'family_name' => 'Sulu',
                    'given_name' => 'Hikaru',
                ]);

                return new MockResponse($response);
            },
        ];

        $this->httpClient->setResponseFactory($expectedRequests);

        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn('https://sulu.io/admin');

        $this->userRepository->findOneBy(Argument::any())->willReturn(null);

        $user = new User();
        $this->userRepository->createNew()->willReturn($user)->shouldBeCalled();

        $contact = new Contact();
        $this->contactRepository->createNew()->willReturn($contact)->shouldBeCalled();

        $persistedUser = null;
        $persistedContact = null;
        $persistedUserRole = null;

        $this->entityManager->persist(Argument::that(function($object) use (&$persistedUser, &$persistedContact, &$persistedUserRole) {
            if ($object instanceof Contact) {
                $persistedContact = $object;
            } elseif ($object instanceof User) {
                $persistedUser = $object;
            } elseif ($object instanceof UserRole) {
                $persistedUserRole = $object;
            } else {
                $this->fail('Unexpected object: ' . $object::class);
            }

            return true;
        }))->shouldBeCalledTimes(3);
        $role = new Role();
        $role->setKey('ADMIN');
        $this->roleRepository->findOneBy(Argument::any())
            ->shouldBeCalled()
            ->willReturn($role);
        $this->entityManager->flush()->shouldBeCalled();

        $expectedUserBadge = new UserBadge('hello@sulu.io', null, [
            'email' => 'hello@sulu.io',
            'family_name' => 'Sulu',
            'given_name' => 'Hikaru',
        ]);

        $result = $this->adapter->createOrUpdateUser($token);

        $this->assertEquals($expectedUserBadge, $result);

        $this->assertNotNull($persistedUser);
        $this->assertSame($persistedUser, $persistedUserRole->getUser());
        $this->assertNotNull($persistedContact);
        $this->assertSame('Hikaru', $persistedContact->getFirstName());
        $this->assertSame('Sulu', $persistedContact->getLastName());
        $this->assertSame('hello@sulu.io', $persistedUser->getEmail());
        $this->assertNotNull($persistedUserRole);
        $this->assertSame($role, $persistedUserRole->getRole());
    }
}
