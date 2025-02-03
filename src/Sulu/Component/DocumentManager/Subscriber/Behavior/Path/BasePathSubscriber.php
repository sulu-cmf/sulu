<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Sulu\Component\DocumentManager\Behavior\Path\BasePathBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the base path for the node.
 */
class BasePathSubscriber implements EventSubscriberInterface
{
    /**
     * @param string $basePath
     */
    public function __construct(private NodeManager $nodeManager, private $basePath)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => ['handlePersist', 500],
        ];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePathBehavior) {
            return;
        }

        $parentNode = $this->nodeManager->createPath($this->basePath);
        $event->setParentNode($parentNode);
    }
}
