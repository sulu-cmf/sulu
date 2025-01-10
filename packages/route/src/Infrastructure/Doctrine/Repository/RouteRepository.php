<?php

namespace Sulu\Route\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RouteRepository implements RouteRepositoryInterface
{
    /**
     * @var EntityRepository<Route>
     */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $this->entityManager->getRepository(Route::class);
    }

    public function add(Route $route): void
    {
        $this->entityManager->persist($route);
    }

    public function findOneBy(array $criteria): ?Route
    {
        return $this->repository->findOneBy($criteria);
    }
}
