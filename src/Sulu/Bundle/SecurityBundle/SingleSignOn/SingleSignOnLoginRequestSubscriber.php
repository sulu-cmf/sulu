<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @final
 *
 * @internal
 *
 * @experimental
 */
class SingleSignOnLoginRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SingleSignOnAdapterProvider $singleSignOnAdapterProvider,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                ['onKernelRequest', 9],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod('POST')) {
            return;
        }

        $route = $request->attributes->get('_route');
        if (!\in_array($route, ['sulu_admin.login_check', 'sulu_security.reset_password.email'])) {
            return;
        }

        $isResetPassword = 'sulu_security.reset_password.email' === $route;
        $identifier = $request->request->get('username') ?? $request->request->get('user');
        $password = $request->request->get('password');

        if (!$identifier || !\is_string($identifier)) {
            return;
        }

        // Todo: Change this, userRepository should not return an error if there was no user found.
        try {
            /** @var ?User $user */
            $user = $this->userRepository->findUserByIdentifier($identifier);
            $email = $user ? ($user->getEmail() ?? $user->getUsername()) : $identifier;
        } catch (NoResultException $e) {
            $email = $identifier;
        }

        $domain = \explode('@', $email)[1] ?? null;
        if (!$domain) {
            if ($password || $isResetPassword) {
                return;
            }

            $event->setResponse(new JsonResponse(['method' => 'json_login'], 200));
            $event->stopPropagation();

            return;
        }

        $adapter = $this->singleSignOnAdapterProvider->getAdapterByDomain($domain);
        if (!$adapter) {
            if ($password || $isResetPassword) {
                return;
            }

            $event->setResponse(new JsonResponse(['method' => 'json_login'], 200));
            $event->stopPropagation();

            return;
        }

        $adminRoute = $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $loginUrl = $adapter->generateLoginUrl($request, $adminRoute, $domain);
        $event->setResponse(new JsonResponse(['method' => 'redirect', 'url' => $loginUrl], 200));
        $event->stopPropagation();
    }
}
