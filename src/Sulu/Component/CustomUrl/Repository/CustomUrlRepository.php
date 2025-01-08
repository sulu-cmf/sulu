<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\Webspace\CustomUrl as WebspaceCustomUrl;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/** @extends ServiceEntityRepository<CustomUrl> */
class CustomUrlRepository extends ServiceEntityRepository implements CustomUrlRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly GeneratorInterface $customUrlGenerator,
        private readonly WebspaceManagerInterface $webspaceManager,
    ) {
        parent::__construct($registry, CustomUrl::class);
    }

    public function findByWebspaceKey(string $webspaceKey): RowsIterator
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        return $this->findByWebspaceAndBaseDomains(
            $webspaceKey,
            baseDomains: \array_map(
                fn (WebspaceCustomUrl $customUrl): string => $customUrl->getUrl(),
                $webspace->getCustomUrls($this->environment),
            )
        );
    }

    /**
     * @param string[] $baseDomains
     */
    public function findByWebspaceAndBaseDomains(string $webspaceKey, array $baseDomains = []): RowsIterator
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.webspace = :webspace')
            ->setParameter('webspace', $webspaceKey)
        ;

        if ([] !== $baseDomains) {
            $expr = $queryBuilder->expr();
            $queryBuilder->andWhere($expr->in('c.baseDomain', $baseDomains));
        }

        /** @var array<CustomUrl> $result */
        $result = $queryBuilder->getQuery()->getResult();

        $uuids = \array_map(fn (CustomUrl $url) => $url->getTargetDocument(), $result);

        $targets = $this->contentRepository->findByUuids(
            \array_unique($uuids),
            null,
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        return new RowsIterator($result, $targets, $this->customUrlGenerator);
    }

    public function findNewestPublishedByUrl(string $url, string $webspace, ?string $locale = null): ?CustomUrl
    {
        return $this->createQueryBuilder('c')
            ->join('c.routes', 'r')
            ->andWhere('c.targetDocument IS NOT NULL')
            ->andWhere('c.published = 1')
            ->andWhere('c.webspace = :webspace')
            ->andWhere('r.path = :route')
            ->setParameter('route', $url)
            ->setParameter('webspace', $webspace)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByTarget(UuidBehavior $page): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.targetDocument = :id')
            ->setParameter('id', $page->getUuid());

        if ($page instanceof WebspaceBehavior) {
            $qb->andWhere('c.webspace = :webspace')
                ->setParameter('webspace', $page->getWebspaceName());
        }

        return $qb->getQuery()->execute();
    }

    public function findPathsByWebspace(string $webspace): array
    {
        $result = $this->findBy(['webspace' => $webspace]);

        return \array_map(
            fn (CustomUrl $url) => $this->customUrlGenerator->generate($url->getBaseDomain(), $url->getDomainParts()),
            $result
        );
    }

    public function deleteByIds(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->delete($this->_entityName, 'c')
            ->where($expr->in('c.id', $ids))
            ->getQuery()
            ->execute();
    }
}
