<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\EventListener;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Listen to the cache events, sets the needed headers and cookies for audience targeting.
 */
class AudienceTargetingCacheListener implements EventSubscriberInterface
{
    public const TARGET_GROUP_URL = '/_sulu_target_group';

    public const TARGET_GROUP_HEADER = 'X-Sulu-Target-Group';

    public const TARGET_GROUP_COOKIE = '_svtg';

    public const TARGET_GROUP_COOKIE_LIFETIME = 2147483647;

    public const VISITOR_SESSION_COOKIE = '_svs';

    public const USER_CONTEXT_URL_HEADER = 'X-Forwarded-URL';

    protected $hadValidTargetGroupCookie = false;

    public function preHandle(CacheEvent $cacheEvent): void
    {
        $request = $cacheEvent->getRequest();

        // the friendsofsymfony/http-cache-bundle package uses the "internalRequest" attribute to mark internal requests
        // return early in this case to prevent loops on requests "/_sulu_target_group" or "/_fos_user_context_hash"
        if ($request->attributes->get('internalRequest', false)) {
            return;
        }

        $this->hadValidTargetGroupCookie = $this->setTargetGroupHeader(
            $request,
            $cacheEvent->getKernel()
        );
    }

    public function postHandle(CacheEvent $cacheEvent): void
    {
        $request = $cacheEvent->getRequest();

        if ($request->attributes->get('internalRequest', false)) {
            return;
        }

        $response = $cacheEvent->getResponse();

        if (!$this->hadValidTargetGroupCookie) {
            $this->setTargetGroupCookie($response, $request);
        }

        if (\in_array(static::TARGET_GROUP_HEADER, $response->getVary())) {
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
        }
    }

    /**
     * Sets the target group header based on an existing cookie, so that the application can adapt the content according
     * to it. If the cookie didn't exist yet, another request is fired in order to set the value for the cookie.
     *
     * Returns true if the cookie was already set and false otherwise.
     *
     * @return bool
     */
    private function setTargetGroupHeader(Request $request, CacheInvalidation $kernel): bool
    {
        $hadValidTargetGroup = true;
        $visitorTargetGroup = $request->cookies->get(static::TARGET_GROUP_COOKIE);
        $visitorSession = $request->cookies->get(static::VISITOR_SESSION_COOKIE);

        if (null === $visitorTargetGroup || null === $visitorSession) {
            $hadValidTargetGroup = false;
            $visitorTargetGroup = $this->requestTargetGroup($request, $kernel, $visitorTargetGroup);
        }

        if (null !== $visitorTargetGroup && $request->isMethodCacheable()) {
            // add the target group as separate header to vary on it
            $request->headers->set(static::TARGET_GROUP_HEADER, (string) $visitorTargetGroup);
        }

        return $hadValidTargetGroup;
    }

    /**
     * Sends a request to the application to determine the target group of the current visitor.
     *
     * @return ?string
     */
    private function requestTargetGroup(Request $request, CacheInvalidation $kernel, ?int $currentTargetGroup = null): ?string
    {
        $targetGroupRequest = Request::create(
            static::TARGET_GROUP_URL,
            Request::METHOD_GET,
            [],
            [],
            [],
            $request->server->all()
        );

        $targetGroupRequest->attributes->set('internalRequest', true);

        if ($currentTargetGroup) {
            $targetGroupRequest->headers->set(static::TARGET_GROUP_HEADER, $currentTargetGroup);
        }

        $targetGroupRequest->headers->set(static::USER_CONTEXT_URL_HEADER, $request->getUri());

        try {
            $targetGroupResponse = $kernel->handle($targetGroupRequest, HttpKernelInterface::MAIN_REQUEST, false);
        } catch (NotFoundHttpException $e) {
            // happens on command execution in the admin context because there is no target-group-url route registered
            return null;
        }

        return $targetGroupResponse->headers->get(static::TARGET_GROUP_HEADER);
    }

    /**
     * Set the cookie for the target group from the request. Should only be set in case the cookie was not set before.
     */
    private function setTargetGroupCookie(Response $response, Request $request): void
    {
        $response->headers->setCookie(
            Cookie::create(
                static::TARGET_GROUP_COOKIE,
                $request->headers->get(static::TARGET_GROUP_HEADER),
                static::TARGET_GROUP_COOKIE_LIFETIME
            )
        );

        $response->headers->setCookie(
            Cookie::create(
                static::VISITOR_SESSION_COOKIE,
                \time()
            )
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => ['preHandle', 512],
            Events::POST_HANDLE => ['postHandle', -512],
        ];
    }
}
