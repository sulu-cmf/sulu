<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\AccessToken\Oidc\Exception\MissingClaimException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @experimental
 */
class OpenIdSingleSignOnAdapter implements SingleSignOnAdapterInterface
{
    public const OPEN_ID_ATTRIBUTES = '_sulu_security_open_id_attributes';

    /**
     * @param array<string> $translations
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private UserRepositoryInterface $userRepository,
        private EntityManagerInterface $entityManager,
        private ContactRepositoryInterface $contactRepository,
        private RoleRepositoryInterface $roleRepository,
        private UrlGeneratorInterface $urlGenerator,
        private string $endpoint,
        private string $clientId,
        #[\SensitiveParameter]
        private string $clientSecret,
        private string $defaultRoleKey,
        private array $translations,
    ) {
    }

    public function generateLoginUrl(Request $request, string $redirectUrl, string $domain): string
    {
        $openIdConfiguration = $this->httpClient->request('GET', $this->endpoint)->toArray();
        $authorizationEndpoint = $openIdConfiguration['authorization_endpoint'] ?? null;
        if (!$authorizationEndpoint) {
            throw new HttpException(504, 'No authorization_endpoint found in OpenId configuration from: ' . $this->endpoint);
        }

        $authorizationObject = $this->generateAuthorizationUrl(
            $authorizationEndpoint,
            $redirectUrl,
        );

        $authorizationObject['attributes']['domain'] = $domain;

        $request->getSession()->set(self::OPEN_ID_ATTRIBUTES, $authorizationObject['attributes']);

        return $authorizationObject['url'];
    }

    /**
     * @return array{
     *     url: string,
     *     attributes: array<string, string|int>,
     * }
     */
    private function generateAuthorizationUrl(
        string $authenticationEndpoint,
        string $redirectUrl,
        ?string $state = null,
        ?string $nonce = null,
        ?string $codeVerifier = null,
        ?string $codeChallengeMethod = null,
    ): array {
        $state ??= Uuid::v7()->toRfc4122();
        $attributes['state'] = $state;
        $nonce ??= Uuid::v7()->toRfc4122();

        $query = [
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'scope' => 'openid email phone profile address',
            'client_id' => $this->clientId,
            'state' => $state,
            'nonce' => $nonce,
        ];

        if ($codeChallengeMethod) {
            $codeVerifier ??= \base64_encode(\random_bytes(32));
            $codeChallenge = \base64_encode(\hash(match ($codeChallengeMethod) {
                'S256' => 'sha256',
                default => throw new \RuntimeException('Invalid code challenge method'),
            }, $codeVerifier, true));
            $codeChallenge = \rtrim($codeChallenge, '=');
            $codeChallenge = \urlencode($codeChallenge);

            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = $codeChallengeMethod;

            $attributes['codeVerifier'] = $codeVerifier;
        }

        return [
            'url' => $authenticationEndpoint . '?' . \http_build_query($query),
            'attributes' => $attributes,
        ];
    }

    /**
     * @param array<string, string|int|null> $expectedAttributes
     * @param array<string, mixed> $givenAttributes
     */
    public function isAuthorizationValid(array $expectedAttributes, array $givenAttributes): bool
    {
        if (!isset($expectedAttributes['state'])
            || $expectedAttributes['state'] !== $givenAttributes['state']) {
            return false;
        }

        return true;
    }

    public function createOrUpdateUser(string $token): UserBadge
    {
        $openIdConfiguration = $this->httpClient->request('GET', $this->endpoint)->toArray();
        $tokenEndpoint = $openIdConfiguration['token_endpoint'] ?? null;

        if (!$tokenEndpoint) {
            throw new \RuntimeException('No "token_endpoint" found in OpenId configuration from: ' . $this->endpoint);
        }

        $userinfoEndpoint = $openIdConfiguration['userinfo_endpoint'] ?? null;
        if (!$userinfoEndpoint) {
            throw new \RuntimeException('No "userinfo_endpoint" found in OpenId configuration from: ' . $this->endpoint);
        }

        $redirectUrl = $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $tokenUrl = $this->generateTokenUrl(
            $tokenEndpoint,
            $token,
            $redirectUrl,
        );

        $data = $this->httpClient->request(
            'POST',
            $tokenUrl['url'],
            [
                'headers' => $tokenUrl['headers'],
                'body' => $tokenUrl['body'],
            ],
        )->toArray();

        $accessToken = $data['access_token'];

        /** @var array{
         *     sub?: string,
         *     name?: string,
         *     given_name?: string,
         *     family_name?: string,
         *     picture?: string,
         *     email?: string,
         *     email_verified?: bool,
         *     locale?: string,
         *     hd?: string,
         * } $attributes
         */
        $attributes = $this->httpClient->request('GET', $userinfoEndpoint, [
            'auth_bearer' => $accessToken,
        ])->toArray();

        $identifier = $attributes['email'] ?? null;

        if (null === $identifier) {
            throw new MissingClaimException('"email claim not found on OIDC server response".');
        }

        $this->createOrUpdateAdminUser($identifier, $attributes);

        return new UserBadge($identifier, null, $attributes);
    }

    /**
     * @return array{
     *     url: string,
     *     headers: array<string, string>,
     *     body: string,
     * }
     */
    private function generateTokenUrl(string $tokenEndpoint, string $token, string $redirectUrl): array
    {
        return [
            'url' => $tokenEndpoint,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $token,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $redirectUrl,
            ]),
        ];
    }

    /**
     * @param array{
     *     sub?: string,
     *     name?: string,
     *     given_name?: string,
     *     family_name?: string,
     *     picture?: string,
     *     email?: string,
     *     email_verified?: bool,
     *     locale?: string,
     *     hd?: string,
     * } $attributes
     */
    private function createOrUpdateAdminUser(string $email, array $attributes): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            /** @var User $user */
            $user = $this->userRepository->createNew();
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setPassword(Uuid::v7()->toRfc4122()); // create a random password as a password is required
            $user->setSalt(\base64_encode(\random_bytes(32))); // copied from sulu SaltGenerator
            $user->setEnabled(true);

            $contact = $this->contactRepository->createNew();

            $user->setContact($contact);

            $this->entityManager->persist($user);
            $this->entityManager->persist($contact);
        }

        $roleNames = $user->getRoles();

        $role = $this->roleRepository->findOneBy(['key' => $this->defaultRoleKey]);

        if (!$role instanceof Role) {
            throw new \RuntimeException('Role with Key "' . $this->defaultRoleKey . '" not found for OIDC user: "' . $email . '"');
        }

        if (!\in_array($role->getIdentifier(), $roleNames, true)) {
            $defaultRoleKey = new UserRole();
            $defaultRoleKey->setRole($role);
            $defaultRoleKey->setUser($user);
            $defaultRoleKey->setLocale('["en", "de"]');
            $user->addUserRole($defaultRoleKey);
            $this->entityManager->persist($defaultRoleKey);
        }

        $contact = $user->getContact();
        $contact->setFirstName($attributes['given_name'] ?? '');
        $contact->setLastName($attributes['family_name'] ?? '');
        $locale = (isset($attributes['locale']) && \in_array($attributes['locale'], $this->translations, true)) ? $attributes['locale'] : ($user instanceof User && $user->getLocale() ? $user->getLocale() : 'en');
        $user->setLocale($locale);

        $this->entityManager->flush();
    }
}
