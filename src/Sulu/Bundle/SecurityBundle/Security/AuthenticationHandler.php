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

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Called after a user gets authenticated at the admin firewall
 * Generates the response (either JSON or a Redirect depending on if the request is a XmlHttpRequest or not).
 *
 * @internal this class is internal bridge to the Symfony security system and your application should not get contact with it
 */
class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    /**
     * @param string[] $twoFactorMethods
     */
    public function __construct(private RouterInterface $router, private array $twoFactorMethods = [])
    {
    }

    /**
     * Handler for AuthenticationSuccess. Returns a JsonResponse if request is an AJAX-request.
     * Returns a RedirectResponse otherwise.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $session = $request->getSession();

        // get url to redirect (or return in the JSON-response)
        if ($session->get('_security.admin.target_path')
            && false !== \strpos($session->get('_security.admin.target_path'), '#')
        ) {
            $url = $session->get('_security.admin.target_path');
        } else {
            $url = $this->router->generate('sulu_admin');
        }

        if ($request->isXmlHttpRequest()) {
            $completed = true;
            $twoFactorMethods = [];
            if ($token instanceof TwoFactorTokenInterface) {
                $completed = false;
                $twoFactorMethods = $token->getTwoFactorProviders();
            }

            if (\in_array('trusted_devices', $this->twoFactorMethods)) {
                $twoFactorMethods[] = 'trusted_devices';
            }

            // if AJAX login
            $array = [
                'url' => $url,
                'username' => $token->getUserIdentifier(),
                'completed' => $completed,
                'twoFactorMethods' => $twoFactorMethods,
            ];

            $response = new JsonResponse($array, 200);
        } else {
            // if form login
            $response = new RedirectResponse($url);
        }

        return $response;
    }

    /**
     * Handler for AuthenticationFailure. Returns a JsonResponse if request is an AJAX-request.
     * Returns a Redirect-response otherwise.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->isXmlHttpRequest()) {
            // if AJAX login
            $array = ['message' => $exception->getMessageKey()];
            $response = new JsonResponse($array, 401);
        } else {
            // if form login
            // set authentication exception to session
            $request->getSession()->set(
                \class_exists(SecurityRequestAttributes::class)
                     ? SecurityRequestAttributes::AUTHENTICATION_ERROR
                     : (\class_exists(Security::class)
                        ? Security::AUTHENTICATION_ERROR // BC layer to Symfony <=6.4
                        : SymfonyCoreSecurity::AUTHENTICATION_ERROR), // BC layer to Symfony <=5.4
                $exception
            );
            $response = new RedirectResponse($this->router->generate('sulu_admin'));
        }

        return $response;
    }
}
