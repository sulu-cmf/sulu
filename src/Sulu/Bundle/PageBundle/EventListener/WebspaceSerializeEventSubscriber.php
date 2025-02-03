<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Extends webspace serialization process.
 */
class WebspaceSerializeEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private WebspaceUrlProviderInterface $webspaceUrlProvider,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        private AccessControlManagerInterface $accessControlManager,
        private TokenStorageInterface $tokenStorage,
        private string $environment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $webspace = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($webspace instanceof Webspace)) {
            return;
        }

        $this->appendPortalInformation($webspace, $context, $visitor);
        $this->appendUrls($webspace, $context, $visitor);
        $this->appendCustomUrls($webspace, $context, $visitor);
        $this->appendNavigations($webspace, $context, $visitor);
        $this->appendSegments($webspace, $context, $visitor);
        $this->appendResourceLocatorStrategy($webspace, $context, $visitor);
        $this->appendPermissions($webspace, $context, $visitor);

        $allLocalizations = [];

        foreach ($webspace->getAllLocalizations() as $localization) {
            $allLocalizations[] = $localization->jsonSerialize();
        }

        $visitor->visitProperty(
            new StaticPropertyMetadata(
                '',
                'allLocalizations',
                $allLocalizations
            ),
            $allLocalizations
        );
    }

    /**
     * Extract portal-information and add them to serialization.
     */
    private function appendPortalInformation(Webspace $webspace, Context $context, SerializationVisitorInterface $visitor)
    {
        $portalInformation = $this->webspaceManager->getPortalInformationsByWebspaceKey(
            $this->environment,
            $webspace->getKey()
        );

        $portalInformation = $context->getNavigator()->accept(\array_values($portalInformation));

        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'portalInformation', $portalInformation),
            $portalInformation
        );
    }

    /**
     * Extract urls and add them to serialization.
     */
    private function appendUrls(Webspace $webspace, Context $context, SerializationVisitorInterface $visitor)
    {
        $urls = $this->webspaceUrlProvider->getUrls($webspace, $this->environment);
        $urls = $context->getNavigator()->accept($urls);

        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'urls', $urls),
            $urls
        );
    }

    /**
     * Extract custom-url and add them to serialization.
     */
    private function appendCustomUrls(Webspace $webspace, Context $context, SerializationVisitorInterface $visitor)
    {
        $customUrls = [];
        foreach ($webspace->getPortals() as $portal) {
            $customUrls = \array_merge(
                $customUrls,
                $this->getCustomUrlsForEnvironment($portal, $portal->getEnvironment($this->environment), $context)
            );
        }

        $customUrls = $context->getNavigator()->accept($customUrls);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'customUrls', $customUrls),
            $customUrls
        );
    }

    /**
     * Returns custom-url data with the connected locales.
     *
     * @return array
     */
    private function getCustomUrlsForEnvironment(Portal $portal, Environment $environment, Context $context)
    {
        $customUrls = [];
        foreach ($environment->getCustomUrls() as $customUrl) {
            $customUrl = $context->getNavigator()->accept($customUrl);
            $customUrl['locales'] = $context->getNavigator()->accept($portal->getLocalizations());
            $customUrls[] = $customUrl;
        }

        return $customUrls;
    }

    private function appendNavigations(Webspace $webspace, Context $context, SerializationVisitorInterface $visitor)
    {
        $navigations = [];
        foreach ($webspace->getNavigation()->getContexts() as $navigationContext) {
            $navigations[] = [
                'key' => $navigationContext->getKey(),
                'title' => $navigationContext->getTitle($context->getAttribute('locale')),
            ];
        }

        $navigations = $context->getNavigator()->accept($navigations);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'navigations', $navigations),
            $navigations
        );
    }

    private function appendSegments(Webspace $webspace, Context $context, SerializationVisitorInterface $visitor)
    {
        $segments = [];
        foreach ($webspace->getSegments() as $segment) {
            $segments[] = [
                'key' => $segment->getKey(),
                'title' => $segment->getTitle($context->getAttribute('locale')),
                'default' => $segment->isDefault(),
            ];
        }

        $segments = $context->getNavigator()->accept($segments);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'segments', $segments),
            $segments
        );
    }

    private function appendResourceLocatorStrategy(
        Webspace $webspace,
        Context $context,
        SerializationVisitorInterface $visitor
    ) {
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool
            ->getStrategy($webspace->getResourceLocatorStrategy());

        $serializedResourceLocatorStrategy = [
            'inputType' => $resourceLocatorStrategy->getInputType(),
        ];

        $resourceLocatorStrategy = $context->getNavigator()->accept($serializedResourceLocatorStrategy);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'resourceLocatorStrategy', $serializedResourceLocatorStrategy),
            $serializedResourceLocatorStrategy
        );
    }

    private function appendPermissions(
        Webspace $webspace,
        Context $context,
        SerializationVisitorInterface $visitor
    ) {
        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('sulu.webspaces.' . $webspace->getKey()),
            $this->tokenStorage->getToken()->getUser()
        );

        $permissions = $context->getNavigator()->accept($permissions);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_permissions', $permissions),
            $permissions
        );
    }
}
