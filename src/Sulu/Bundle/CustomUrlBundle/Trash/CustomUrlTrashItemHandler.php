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

namespace Sulu\Bundle\CustomUrlBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRestoredEvent;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class CustomUrlTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private DocumentDomainEventCollectorInterface $documentDomainEventCollector,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param CustomUrl $customUrl
     */
    public function store(object $customUrl, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($customUrl, CustomUrl::class);

        $data = [
            'title' => $customUrl->getTitle(),
            'creator' => $customUrl->getCreator(),
            'created' => $customUrl->getCreated()->format('c'),
            'baseDomain' => $customUrl->getBaseDomain(),
            'domainParts' => $customUrl->getDomainParts(),
            'canonical' => $customUrl->isCanonical(),
            'redirect' => $customUrl->isRedirect(),
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
            'targetUuid' => $customUrl->getTargetDocument(),
            'targetLocale' => $customUrl->getTargetLocale(),
            'webspaceKey' => $customUrl->getWebspace(),
        ];

        return $this->trashItemRepository->create(
            CustomUrl::RESOURCE_KEY,
            (string) $customUrl->getId(),
            $customUrl->getTitle(),
            $data,
            null,
            $options,
            CustomUrlAdmin::getCustomUrlSecurityContext($customUrl->getWebspace()),
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        $customUrl = new CustomUrl();
        $customUrl->setId($id);
        $customUrl->setTitle($data['title']);
        $customUrl->setCreator($data['creator']);
        $customUrl->setCreated(new \DateTime($data['created']));
        $customUrl->setBaseDomain($data['baseDomain']);
        $customUrl->setDomainParts($data['domainParts']);
        $customUrl->setCanonical($data['canonical']);
        $customUrl->setRedirect($data['redirect']);
        $customUrl->setNoFollow($data['noFollow']);
        $customUrl->setNoIndex($data['noIndex']);
        $customUrl->setTargetDocument($data['targetUuid']);
        $customUrl->setTargetLocale($data['targetLocale']);
        $customUrl->setWebspace($data['webspaceKey']);
        $customUrl->setPublished(false);

        $this->entityManager->persist($customUrl);
        $this->documentDomainEventCollector->collect(new CustomUrlRestoredEvent($customUrl, $data));
        $this->entityManager->flush();

        return $customUrl;
    }

    public static function getResourceKey(): string
    {
        return CustomUrl::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            CustomUrlAdmin::LIST_VIEW,
            ['webspace' => 'webspace']
        );
    }
}
