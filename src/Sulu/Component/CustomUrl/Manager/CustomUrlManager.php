<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlCreatedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlModifiedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRemovedEvent;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl as CustomUrlEntity;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CustomUrlManager implements CustomUrlManagerInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private string $environment,
        private DocumentDomainEventCollectorInterface $documentDomainEventCollector,
        private PropertyAccessor $propertyAccess,
        private EntityManagerInterface $entityManager,
        private GeneratorInterface $generator,
    ) {
    }

    public function create(string $webspaceKey, array $data): CustomUrlEntity
    {
        $customUrl = new CustomUrlEntity();
        $this->bind($customUrl, $data);
        $customUrl->setWebspace($webspaceKey);

        $this->addHistoryEntry($customUrl);
        $this->entityManager->persist($customUrl);
        $this->entityManager->flush();

        $this->documentDomainEventCollector->collect(new CustomUrlCreatedEvent($customUrl, $data));

        return $customUrl;
    }

    public function save(CustomUrlEntity $customUrl, array $data): void
    {
        $this->bind($customUrl, $data);

        $this->addHistoryEntry($customUrl);
        $this->entityManager->flush();

        $this->documentDomainEventCollector->collect(new CustomUrlModifiedEvent($customUrl, $data));
    }

    public function deleteByIds(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        $entities = $this->customUrlRepository->findBy(['id' => $ids]);
        foreach ($entities as $entity) {
            $this->documentDomainEventCollector->collect(new CustomUrlRemovedEvent($entity));
        }

        $this->customUrlRepository->deleteByIds($ids);
    }

    private function addHistoryEntry(CustomUrlEntity $customUrl): void
    {
        $customUrlRoute = new CustomUrlRoute(
            $customUrl,
            $this->generator->generate($customUrl->getBaseDomain(), $customUrl->getDomainParts())
        );

        $customUrl->addRoute($customUrlRoute);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bind(CustomUrlEntity $document, $data): void
    {
        // TODO: Use the actual setters
        foreach ($data as $key => $value) {
            if ('_hash' === $key || 'routes' === $key) {
                continue;
            }

            $this->propertyAccess->setValue($document, $key, $value);
        }
    }
}
