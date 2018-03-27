<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\EventListener;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TargetGroupSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var bool
     */
    private $preview;

    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var string
     */
    private $targetGroupUrl;

    /**
     * @var string
     */
    private $targetGroupHitUrl;

    /**
     * @var string
     */
    private $urlHeader;

    /**
     * @var string
     */
    private $referrerHeader;

    /**
     * @var string
     */
    private $uuidHeader;

    /**
     * @var string
     */
    private $targetGroupHeader;

    /**
     * @var string
     */
    private $targetGroupCookie;

    /**
     * @var string
     */
    private $visitorSessionCookie;

    /**
     * @param \Twig_Environment $twig
     * @param bool $preview
     * @param TargetGroupStoreInterface $targetGroupStore
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param TargetGroupRepositoryInterface $targetGroupRepository
     * @param string $targetGroupUrl
     * @param string $targetGroupHitUrl
     * @param string $urlHeader
     * @param string $referrerHeader
     * @param string $uuidHeader
     * @param string $targetGroupHeader
     * @param string $targetGroupCookie
     * @param string $visitorSessionCookie
     */
    public function __construct(
        \Twig_Environment $twig,
        $preview,
        TargetGroupStoreInterface $targetGroupStore,
        TargetGroupEvaluatorInterface $targetGroupEvaluator,
        TargetGroupRepositoryInterface $targetGroupRepository,
        $targetGroupUrl,
        $targetGroupHitUrl,
        $urlHeader,
        $referrerHeader,
        $uuidHeader,
        $targetGroupHeader,
        $targetGroupCookie,
        $visitorSessionCookie
    ) {
        $this->twig = $twig;
        $this->preview = $preview;
        $this->targetGroupStore = $targetGroupStore;
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->targetGroupUrl = $targetGroupUrl;
        $this->targetGroupHitUrl = $targetGroupHitUrl;
        $this->urlHeader = $urlHeader;
        $this->referrerHeader = $referrerHeader;
        $this->uuidHeader = $uuidHeader;
        $this->targetGroupHeader = $targetGroupHeader;
        $this->targetGroupCookie = $targetGroupCookie;
        $this->visitorSessionCookie = $visitorSessionCookie;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['setTargetGroup'],
            ],
            KernelEvents::RESPONSE => [
                ['addVaryHeader'],
                ['addSetCookieHeader'],
                ['addTargetGroupHitScript'],
            ],
        ];
    }

    /**
     * Evaluates the cookie holding the target group information. This has only an effect if there is no cache used,
     * since in that case the cache already did it.
     *
     * @param GetResponseEvent $event
     */
    public function setTargetGroup(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($targetGroupId = $request->headers->get($this->targetGroupHeader)) {
            $this->targetGroupStore->setTargetGroupId($targetGroupId);
        } elseif ($targetGroupId = $request->cookies->get($this->targetGroupCookie)) {
            $visitorSession = $request->cookies->get($this->visitorSessionCookie);
            if ($visitorSession) {
                $this->targetGroupStore->setTargetGroupId($targetGroupId);

                return;
            }

            $targetGroup = $this->targetGroupEvaluator->evaluate(
                TargetGroupRuleInterface::FREQUENCY_SESSION,
                $this->targetGroupRepository->find($targetGroupId)
            );

            if ($targetGroup) {
                $this->targetGroupStore->updateTargetGroupId($targetGroup->getId());
            }
        } elseif ($request->getPathInfo() !== $this->targetGroupUrl) {
            // this should not happen on the endpoint for the cache, because it is set there manually as a header
            $targetGroup = $this->targetGroupEvaluator->evaluate();

            $targetGroupId = 0;
            if ($targetGroup) {
                $targetGroupId = $targetGroup->getId();
            }

            $this->targetGroupStore->updateTargetGroupId($targetGroupId);
        }
    }

    /**
     * Adds the vary header on the response, so that the cache takes the target group into account.
     *
     * @param FilterResponseEvent $event
     */
    public function addVaryHeader(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->targetGroupStore->hasInfluencedContent() && $request->getRequestUri() !== $this->targetGroupUrl) {
            $response->setVary($this->targetGroupHeader, false);
        }
    }

    /**
     * Adds the SetCookie header for the target group, if the user context has changed. In addition to that a second
     * cookie without a lifetime is set, whose expiration marks a new session.
     *
     * @param FilterResponseEvent $event
     */
    public function addSetCookieHeader(FilterResponseEvent $event)
    {
        if (!$this->targetGroupStore->hasChangedTargetGroup()
            || $event->getRequest()->getPathInfo() === $this->targetGroupUrl
        ) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->setCookie(
            new Cookie(
                $this->targetGroupCookie,
                $this->targetGroupStore->getTargetGroupId(true),
                AudienceTargetingCacheListener::TARGET_GROUP_COOKIE_LIFETIME
            )
        );

        $response->headers->setCookie(
            new Cookie(
                $this->visitorSessionCookie,
                time()
            )
        );
    }

    /**
     * Adds a script for triggering an ajax request, which updates the target group on every hit.
     *
     * @param FilterResponseEvent $event
     */
    public function addTargetGroupHitScript(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->preview
            || 0 !== strpos($response->headers->get('Content-Type'), 'text/html')
            || Request::METHOD_GET !== $request->getMethod()
        ) {
            return;
        }

        $script = $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig', [
            'url' => $this->targetGroupHitUrl,
            'urlHeader' => $this->urlHeader,
            'refererHeader' => $this->referrerHeader,
            'uuidHeader' => $this->uuidHeader,
            'uuid' => $request->attributes->has('structure') ? $request->attributes->get('structure')->getUuid() : null,
        ]);

        $response->setContent(str_replace(
            '</body>',
            $script . '</body>',
            $response->getContent()
        ));
    }
}
