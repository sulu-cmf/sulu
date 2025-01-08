<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class PreviewLinkRepository implements PreviewLinkRepositoryInterface
{
    /**
     * @var EntityRepository<PreviewLinkInterface>
     */
    protected $entityRepository;

    public function __construct(
        protected EntityManager $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(PreviewLinkInterface::class);
    }

    public function create(string $resourceKey, string $resourceId, string $locale, array $options): PreviewLinkInterface
    {
        $token = $this->generateToken();

        /** @var class-string<PreviewLinkInterface> $className */
        $className = $this->entityRepository->getClassName();

        return $className::create($token, $resourceKey, $resourceId, $locale, $options);
    }

    public function findByResource(string $resourceKey, string $resourceId, string $locale): ?PreviewLinkInterface
    {
        /** @var PreviewLinkInterface|null $previewLink */
        $previewLink = $this->entityRepository->findOneBy(['resourceKey' => $resourceKey, 'resourceId' => $resourceId, 'locale' => $locale]);

        return $previewLink;
    }

    public function findByToken(string $token): ?PreviewLinkInterface
    {
        /** @var PreviewLinkInterface|null $previewLink */
        $previewLink = $this->entityRepository->findOneBy(['token' => $token]);

        return $previewLink;
    }

    public function add(PreviewLinkInterface $previewLink): void
    {
        $this->entityManager->persist($previewLink);
    }

    public function remove(PreviewLinkInterface $previewLink): void
    {
        $this->entityManager->remove($previewLink);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }

    protected function generateToken(): string
    {
        $token = \substr(\md5(Uuid::v7()->__toString()), 0, 12);
        if ($this->findByToken($token)) {
            return $this->generateToken();
        }

        return $token;
    }
}
