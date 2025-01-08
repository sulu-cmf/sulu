<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class CustomUrlTrashSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $hasPendingTrashItem = false;

    public function __construct(
        private TrashManagerInterface $trashManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::REMOVE => ['storeCustomUrlToTrash', 1024],
            Events::FLUSH => 'flushTrashItem',
            Events::CLEAR => 'clearPendingTrashItem',
        ];
    }

    public function storeCustomUrlToTrash(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof CustomUrl) {
            return;
        }

        $this->trashManager->store(CustomUrl::RESOURCE_KEY, $document);
        $this->hasPendingTrashItem = true;
    }

    public function flushTrashItem(FlushEvent $event): void
    {
        if (!$this->hasPendingTrashItem) {
            return;
        }

        $this->entityManager->flush();
        $this->hasPendingTrashItem = false;
    }

    public function clearPendingTrashItem(ClearEvent $event): void
    {
        $this->hasPendingTrashItem = false;
    }
}
