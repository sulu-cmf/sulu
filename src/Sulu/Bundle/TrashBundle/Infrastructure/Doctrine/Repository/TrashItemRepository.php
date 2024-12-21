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

namespace Sulu\Bundle\TrashBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

final class TrashItemRepository implements TrashItemRepositoryInterface
{
    /**
     * @var EntityRepository<TrashItemInterface>
     */
    private $entityRepository;

    public function __construct(private EntityManagerInterface $entityManager, private Security|SymfonyCoreSecurity|null $security)
    {
        $this->entityRepository = $this->entityManager->getRepository(TrashItemInterface::class);
    }

    public function create(
        string $resourceKey,
        string $resourceId,
        $resourceTitle,
        array $restoreData,
        ?string $restoreType,
        array $restoreOptions,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): TrashItemInterface {
        /** @var class-string<TrashItemInterface> $className */
        $className = $this->entityRepository->getClassName();

        /** @var TrashItemInterface $trashItem */
        $trashItem = new $className();

        $trashItem
            ->setResourceKey($resourceKey)
            ->setResourceId($resourceId)
            ->setRestoreData($restoreData)
            ->setRestoreType($restoreType)
            ->setRestoreOptions($restoreOptions)
            ->setResourceSecurityContext($resourceSecurityContext)
            ->setResourceSecurityObjectType($resourceSecurityObjectType)
            ->setResourceSecurityObjectId($resourceSecurityObjectId)
            ->setUser($this->getCurrentUser());

        if (\is_string($resourceTitle)) {
            $trashItem->setResourceTitle($resourceTitle);
        }

        if (\is_array($resourceTitle)) {
            foreach ($resourceTitle as $locale => $title) {
                $trashItem->setResourceTitle($title, $locale);
            }
        }

        return $trashItem;
    }

    public function add(TrashItemInterface $trashItem): void
    {
        $this->entityManager->persist($trashItem);
    }

    public function remove(TrashItemInterface $trashItem): void
    {
        $this->entityManager->remove($trashItem);
    }

    public function findOneBy(array $criteria): ?TrashItemInterface
    {
        return $this->entityRepository->findOneBy($criteria);
    }

    public function getOneBy(array $criteria): TrashItemInterface
    {
        $trashItem = $this->findOneBy($criteria);

        if (null === $trashItem) {
            throw new TrashItemNotFoundException($criteria);
        }

        return $trashItem;
    }

    private function getCurrentUser(): ?UserInterface
    {
        if (null === $this->security) {
            return null;
        }

        $user = $this->security->getUser();

        if (!($user instanceof UserInterface)) {
            return null;
        }

        return $user;
    }
}
