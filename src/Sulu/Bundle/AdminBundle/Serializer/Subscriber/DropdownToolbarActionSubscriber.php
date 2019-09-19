<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\AdminBundle\Admin\Routing\DropdownToolbarAction;
use Symfony\Component\Translation\TranslatorInterface;

class DropdownToolbarActionSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => DropdownToolbarAction::class,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $dropdownToolbarAction = $event->getObject();
        $visitor = $event->getVisitor();

        $options = $dropdownToolbarAction->getOptions();
        $options['label'] = $this->translator->trans($options['label'], [], 'admin');
        $serializedOptions = $visitor->visitArray($options, [], $event->getContext());
        $visitor->setData('options', $serializedOptions);
    }
}
