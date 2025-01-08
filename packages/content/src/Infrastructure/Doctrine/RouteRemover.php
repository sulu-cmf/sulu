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

namespace Sulu\Content\Infrastructure\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @internal
 *
 * @final
 */
class RouteRemover implements EventSubscriber
{
    /**
     * @param array<string|int, array{resource_key: string, entityClass?: string}> $routeMappings
     */
    public function __construct(
        private ContentMetadataInspectorInterface $contentMetadataInspector,
        private RouteRepositoryInterface $routeRepository,
        private array $routeMappings,
    ) {
    }

    public function getSubscribedEvents()
    {
        return [Events::preRemove];
    }

    public function preRemove(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();
        if (!$object instanceof ContentRichEntityInterface) {
            return; // @codeCoverageIgnore
        }

        $dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($object::class);
        $resourceKey = $dimensionContentClass::getResourceKey();

        $entityClass = null;
        foreach ($this->routeMappings as $key => $mapping) {
            if ($resourceKey === $mapping['resource_key']) {
                /** @var class-string $entityClass */
                $entityClass = $mapping['entityClass'] ?? $key;
                break;
            }
        }

        if (!$entityClass) {
            return;
        }

        foreach ($this->routeRepository->findAllByEntity($entityClass, (string) $object->getId()) as $route) {
            $event->getEntityManager()->remove($route);
        }
    }
}
