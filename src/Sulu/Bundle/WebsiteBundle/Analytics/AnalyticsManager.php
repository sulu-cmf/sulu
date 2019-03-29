<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Analytics;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;

/**
 * Manages analytics.
 */
class AnalyticsManager implements AnalyticsManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AnalyticsRepository
     */
    private $analyticsRepository;

    /**
     * @var DomainRepository
     */
    private $domainRepository;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnalyticsRepository $analyticsRepository,
        DomainRepository $domainRepository,
        string $environment
    ) {
        $this->entityManager = $entityManager;
        $this->analyticsRepository = $analyticsRepository;
        $this->domainRepository = $domainRepository;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($webspaceKey)
    {
        return $this->analyticsRepository->findByWebspaceKey($webspaceKey);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->analyticsRepository->findById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create($webspaceKey, $data)
    {
        $entity = new Analytics();
        $this->setData($entity, $webspaceKey, $data);

        $this->entityManager->persist($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, $data)
    {
        $entity = $this->find($id);
        $this->setData($entity, $entity->getWebspaceKey(), $data);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->entityManager->remove($this->entityManager->getReference(Analytics::class, $id));
    }

    /**
     * {@inheritdoc}
     */
    public function removeMultiple(array $ids)
    {
        foreach ($ids as $id) {
            $this->entityManager->remove($this->entityManager->getReference(Analytics::class, $id));
        }
    }

    /**
     * Set data to given key.
     *
     * @param Analytics $analytics
     * @param string $webspaceKey
     * @param array $data
     */
    private function setData(Analytics $analytics, $webspaceKey, $data)
    {
        $analytics->setTitle($this->getValue($data, 'title'));
        $analytics->setType($this->getValue($data, 'type'));
        $analytics->setContent($this->getValue($data, 'content', ''));
        $analytics->setAllDomains($this->getValue($data, 'allDomains', false));
        $analytics->setWebspaceKey($webspaceKey);

        $analytics->clearDomains();

        if (!$analytics->isAllDomains()) {
            foreach ($this->getValue($data, 'domains', []) as $domain) {
                $domainEntity = $this->findOrCreateNewDomain($domain);
                $analytics->addDomain($domainEntity);
            }
        }
    }

    private function findOrCreateNewDomain(string $domain): Domain
    {
        $domainEntity = $this->domainRepository->findByUrlAndEnvironment($domain, $this->environment);

        if (null !== $domainEntity) {
            return $domainEntity;
        }

        $domainEntity = new Domain();
        $domainEntity->setUrl($domain);
        $domainEntity->setEnvironment($this->environment);

        $this->entityManager->persist($domainEntity);

        return $domainEntity;
    }

    /**
     * Returns property of data with given name.
     * If this property does not exists this function returns given default.
     *
     * @param string $data
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getValue($data, $name, $default = null)
    {
        if (!array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
