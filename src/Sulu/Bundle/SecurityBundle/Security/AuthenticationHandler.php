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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Called after a user gets authenticated at the admin firewall
 * Generates the response (either JSON or a Redirect depending on if the request is a XmlHttpRequest or not).
 *
 * @internal this class is internal bridge to the Symfony security system and your application should not get contact with it
 */
class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string[]
     */
    private array $twoFactorMethods;

    /**
     * @param string[] $twoFactorMethods
     */
    public function __construct(RouterInterface $router, array $twoFactorMethods = [])
    {
        $this->router = $router;
        $this->twoFactorMethods = $twoFactorMethods;
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
            $array = ['message' => $exception->getMessage()];
            $response = new JsonResponse($array, 401);
        } else {
            // if form login
            // set authentication exception to session
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $response = new RedirectResponse($this->router->generate('sulu_admin'));
        }

        return $response;
    }
}
