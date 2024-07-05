<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Domain\Repository\ActivityRepositoryInterface;

class ActivityRepository implements ActivityRepositoryInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository<ActivityInterface>
     */
    protected $entityRepository;

    /**
     * @var bool
     */
    protected $shouldPersistPayload;

    public function __construct(EntityManager $entityManager, bool $shouldPersistPayload)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(ActivityInterface::class);
        $this->shouldPersistPayload = $shouldPersistPayload;
    }

    public function createFromDomainEvent(DomainEvent $domainEvent): ActivityInterface
    {
        /** @var class-string<ActivityInterface> $className */
        $className = $this->entityRepository->getClassName();

        /** @var ActivityInterface $activity */
        $activity = new $className();
        $activity->setType($domainEvent->getEventType());
        $activity->setContext($domainEvent->getEventContext());
        $activity->setPayload($domainEvent->getEventPayload());
        $activity->setTimestamp($domainEvent->getEventDateTime());
        $activity->setBatch($domainEvent->getEventBatch());
        $activity->setUser($domainEvent->getUser());
        $activity->setResourceKey($domainEvent->getResourceKey());
        $activity->setResourceId($domainEvent->getResourceId());
        $activity->setResourceLocale($domainEvent->getResourceLocale());
        $activity->setResourceWebspaceKey($domainEvent->getResourceWebspaceKey());
        $activity->setResourceTitle($domainEvent->getResourceTitle());
        $activity->setResourceTitleLocale($domainEvent->getResourceTitleLocale());
        $activity->setResourceSecurityContext($domainEvent->getResourceSecurityContext());
        $activity->setResourceSecurityObjectType($domainEvent->getResourceSecurityObjectType());
        $activity->setResourceSecurityObjectId($domainEvent->getResourceSecurityObjectId());

        if ($this->shouldPersistPayload) {
            $activity->setPayload($domainEvent->getEventPayload());
        }

        return $activity;
    }

    protected function getInsertQueryBuilder(ActivityInterface $activity): QueryBuilder
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->entityRepository->getClassName());

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->insert($classMetadata->getTableName())
            ->setValue($classMetadata->getColumnName('type'), $queryBuilder->createNamedParameter($activity->getType()))
            ->setValue($classMetadata->getColumnName('context'), $queryBuilder->createNamedParameter(\json_encode($activity->getContext())))
            ->setValue($classMetadata->getColumnName('timestamp'), $queryBuilder->createNamedParameter($activity->getTimestamp()->format('Y-m-d H:i:s')))
            ->setValue($classMetadata->getColumnName('batch'), $queryBuilder->createNamedParameter($activity->getBatch()))
            ->setValue($classMetadata->getColumnName('resourceKey'), $queryBuilder->createNamedParameter($activity->getResourceKey()))
            ->setValue($classMetadata->getColumnName('resourceId'), $queryBuilder->createNamedParameter($activity->getResourceId()))
            ->setValue($classMetadata->getColumnName('resourceLocale'), $queryBuilder->createNamedParameter($activity->getResourceLocale()))
            ->setValue($classMetadata->getColumnName('resourceWebspaceKey'), $queryBuilder->createNamedParameter($activity->getResourceWebspaceKey()))
            ->setValue($classMetadata->getColumnName('resourceTitle'), $queryBuilder->createNamedParameter($activity->getResourceTitle()))
            ->setValue($classMetadata->getColumnName('resourceTitleLocale'), $queryBuilder->createNamedParameter($activity->getResourceTitleLocale()))
            ->setValue($classMetadata->getColumnName('resourceSecurityContext'), $queryBuilder->createNamedParameter($activity->getResourceSecurityContext()))
            ->setValue($classMetadata->getColumnName('resourceSecurityObjectType'), $queryBuilder->createNamedParameter($activity->getResourceSecurityObjectType()))
            ->setValue($classMetadata->getColumnName('resourceSecurityObjectId'), $queryBuilder->createNamedParameter($activity->getResourceSecurityObjectId()));

        if (null !== $activity->getUser()) {
            $queryBuilder->setValue($classMetadata->getColumnName('userId'), $queryBuilder->createNamedParameter($activity->getUser()->getId()));
        }

        if ($this->shouldPersistPayload) {
            $queryBuilder->setValue($classMetadata->getColumnName('payload'), $queryBuilder->createNamedParameter(\json_encode($activity->getPayload())));
        }

        // set value of id explicitly if class has a pre-insert identity-generator to be compatible with postgresql
        if (!$classMetadata->idGenerator->isPostInsertGenerator()) {
            $queryBuilder->setValue(
                $classMetadata->getColumnName('id'),
                \method_exists($classMetadata->idGenerator, 'generateId')
                    ? $classMetadata->idGenerator->generateId($this->entityManager, $activity)
                    : $classMetadata->idGenerator->generate($this->entityManager, $activity) // remove when doctrine/orm or greater 2.18 is min version
            );
        }

        return $queryBuilder;
    }

    public function addAndCommit(ActivityInterface $activity): void
    {
        // use query-builder to insert only given entity instead of flushing all managed entities via the entity-manager
        // this prevents flushing unrelated changes and allows to call this method in a postFlush event-listener
        $queryBuilder = $this->getInsertQueryBuilder($activity);
        $queryBuilder->execute();
    }
}
