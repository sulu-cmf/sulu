<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventSubscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;

/**
 * Extends analytics serialization process.
 */
class AnalyticsSerializeEventSubscriber implements EventSubscriberInterface
{
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
        $analytics = $event->getObject();

        if (!($analytics instanceof AnalyticsInterface)) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        $content = $analytics->getContent();

        switch ($analytics->getType()) {
            case 'google':
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'google_key', $content),
                    $content
                );
                break;
            case 'google_tag_manager':
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'google_tag_manager_key', $content),
                    $content
                );
                break;
            case 'matomo':
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'matomo_id', $content['siteId']),
                    $content['siteId']
                );
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'matomo_url', $content['url']),
                    $content['url']
                );
                break;
            case 'custom':
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'custom_script', $content['value']),
                    $content['value']
                );
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'custom_position', $content['position']),
                    $content['position']
                );
                break;
        }
    }
}
