<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the path on the document.
 */
class PathSubscriber implements EventSubscriberInterface
{
    public function __construct(private DocumentInspector $documentInspector)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => [
                ['setInitialPath', 0],
                ['setFinalPath', -495],
            ],
            Events::HYDRATE => 'setFinalPath',
        ];
    }

    /**
     * Sets the path at the beginning of persisting.
     */
    public function setInitialPath(AbstractMappingEvent $event)
    {
        $this->setPath($event);
    }

    /**
     * Sets the path at the very end, in case the path has been changed in the persisting process.
     */
    public function setFinalPath(AbstractMappingEvent $event)
    {
        $this->setPath($event);
    }

    private function setPath(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof PathBehavior) {
            return;
        }

        $event->getAccessor()->set('path', $this->documentInspector->getPath($document));
    }
}
