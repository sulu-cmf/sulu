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
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterFactoryInterface;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @final
 *
 * @experimental
 */
class OpenIdSingleSignOnAdapterFactory implements SingleSignOnAdapterFactoryInterface
{
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
        private array $translations
    ) {
    }

    public function createAdapter(#[\SensitiveParameter] array $dsn, string $defaultRoleKey): SingleSignOnAdapterInterface
    {
        $endpoint = (($dsn['query']['no-tls'] ?? false) ? 'http' : 'https') . '://' // http://
            . $dsn['host']                                                          // example.org
            . (($dsn['port'] ?? null) ? ':' . $dsn['port'] : '')                    // :8081
            . ($dsn['path'] ?? '');                                                 // /.well-known/openid-configuration

        $clientId = $dsn['user'] ?? '';
        $clientSecret = $dsn['pass'] ?? '';

        return new OpenIdSingleSignOnAdapter(
            $this->httpClient,
            $this->userRepository,
            $this->entityManager,
            $this->contactRepository,
            $this->roleRepository,
            $this->urlGenerator,
            $endpoint,
            $clientId,
            $clientSecret,
            $defaultRoleKey,
            $this->translations,
        );
    }

    public static function getName(): string
    {
        return 'openid';
    }
}
