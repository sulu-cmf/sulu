<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountMediaAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountMediaRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * This Manager handles Account functionality.
 *
 * @extends AbstractContactManager<AccountInterface, AccountApi, AccountAddressEntity>
 */
class AccountManager extends AbstractContactManager implements DataProviderRepositoryInterface
{
    protected $addressEntity = AddressEntity::class;

    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        private AccountFactory $accountFactory,
        private AccountRepositoryInterface $accountRepository,
        private ContactRepository $contactRepository,
        protected MediaRepositoryInterface $mediaRepository,
        protected DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($em, $tagManager, $mediaManager);
    }

    /**
     * Adds an address to the entity.
     *
     * @param AccountInterface $account The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param bool $isMain Defines if the address is the main Address of the contact
     *
     * @return AccountAddressEntity
     *
     * @throws \Exception
     */
    public function addAddress($account, AddressEntity $address, $isMain = false)
    {
        if (!$account || !$address) {
            throw new \Exception('Account and Address cannot be null');
        }
        $accountAddress = new AccountAddressEntity();
        $accountAddress->setAccount($account);
        $accountAddress->setAddress($address);
        if ($isMain) {
            $this->unsetMain($account->getAccountAddresses());
        }
        $accountAddress->setMain($isMain);
        $account->addAccountAddress($accountAddress);
        $address->addAccountAddress($accountAddress);
        $this->em->persist($accountAddress);

        return $accountAddress;
    }

    /**
     * Removes the address relation from a contact and also deletes the address
     * if it has no more relations.
     *
     * @param AccountInterface $account
     * @param AccountAddressEntity $accountAddress
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function removeAddressRelation($account, $accountAddress)
    {
        if (!$account || !$accountAddress) {
            throw new \Exception('Account and AccountAddress cannot be null');
        }

        // Reload address to get all data (including relational data).
        /** @var AddressEntity $address */
        $address = $accountAddress->getAddress();
        $address = $this->em->getRepository(AddressEntity::class)
            ->findById($address->getId());

        $isMain = $accountAddress->getMain();

        // Remove relation.
        $address->removeAccountAddress($accountAddress);
        $account->removeAccountAddress($accountAddress);

        // If was main, set a new one.
        if ($isMain) {
            $this->setMainForCollection($account->getAccountContacts());
        }

        // Delete address if it has no more relations.
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($accountAddress);
    }

    /**
     * Returns a collection of relations to get addresses.
     *
     * @param AccountInterface $entity
     *
     * @return Collection
     */
    public function getAddressRelations($entity)
    {
        return $entity->getAccountAddresses();
    }

    /**
     * Gets account by id.
     *
     * @param int $id
     * @param string $locale
     *
     * @throws EntityNotFoundException
     */
    public function getById($id, $locale)
    {
        $account = $this->accountRepository->findAccountById($id);
        if (!$account) {
            throw new EntityNotFoundException($this->accountRepository->getClassName(), $id);
        }

        return $this->getApiObject($account, $locale);
    }

    /**
     * Returns account entities by ids.
     *
     * @param array $ids
     * @param string $locale
     *
     * @return array
     */
    public function getByIds($ids, $locale)
    {
        if (!\is_array($ids) || 0 === \count($ids)) {
            return [];
        }

        $accounts = $this->accountRepository->findByIds($ids);

        return \array_map(
            function($account) use ($locale) {
                return $this->getApiObject($account, $locale);
            },
            $accounts
        );
    }

    /**
     * Gets account by id - can include relations.
     *
     * @param int $id
     * @param string $locale
     * @param array $includes
     *
     * @return AccountApi
     *
     * @throws EntityNotFoundException
     */
    public function getByIdAndInclude($id, $locale, $includes)
    {
        $account = $this->accountRepository->findAccountById($id, \in_array('contacts', $includes));

        if (!$account) {
            throw new EntityNotFoundException($this->accountRepository->getClassName(), $id);
        }

        return $this->getApiObject($account, $locale);
    }

    /**
     * Returns contacts by account id.
     *
     * @param int $id
     * @param string $locale
     * @param bool $onlyFetchMainAccounts
     *
     * @return array|null
     */
    public function findContactsByAccountId($id, $locale, $onlyFetchMainAccounts = false)
    {
        $contactsEntities = $this->contactRepository->findByAccountId(
            $id,
            null,
            false,
            $onlyFetchMainAccounts
        );

        if (!empty($contactsEntities)) {
            $contacts = [];
            foreach ($contactsEntities as $contact) {
                $contacts[] = new Contact($contact, $locale);
            }

            return $contacts;
        }

        return;
    }

    /**
     * Takes an account-entity and the id of a media and adds the media as the logo of the account.
     * TODO: handle logo adding differently and remove this method (or make it private).
     *
     * @param AccountInterface $account
     * @param int $mediaId
     */
    public function setLogo($account, $mediaId)
    {
        $media = $this->mediaManager->getEntityById($mediaId);
        $account->setLogo($media);
    }

    /**
     * Sets the medias of the given account to the given medias.
     * Currently associated medias are replaced.
     *
     * @param array $mediaIds
     *
     * @throws EntityNotFoundException
     */
    public function setMedias(Account $account, $mediaIds)
    {
        $foundMedias = $this->mediaRepository->findById($mediaIds);
        $foundMediaIds = \array_map(
            function($mediaEntity) {
                return $mediaEntity->getId();
            },
            $foundMedias
        );

        if ($missingMediaIds = \array_diff($mediaIds, $foundMediaIds)) {
            throw new EntityNotFoundException($this->mediaRepository->getClassName(), \reset($missingMediaIds));
        }

        foreach ($account->getMedias() as $media) {
            if (!\in_array($media->getId(), $foundMediaIds)) {
                $account->removeMedia($media);

                $this->domainEventCollector->collect(
                    new AccountMediaRemovedEvent($account, $media)
                );
            }
        }

        foreach ($foundMedias as $media) {
            if (!$account->getMedias()->contains($media)) {
                $account->addMedia($media);

                $this->domainEventCollector->collect(
                    new AccountMediaAddedEvent($account, $media)
                );
            }
        }
    }

    /**
     * Returns all accounts.
     *
     * @param string $locale
     * @param array<string, mixed>|null $filter
     *
     * @return array|null
     */
    public function findAll($locale, $filter = null)
    {
        if ($filter) {
            $accountEntities = $this->accountRepository->findByFilter($filter);
        } else {
            $accountEntities = $this->accountRepository->findAll();
        }

        if (!empty($accountEntities)) {
            $accounts = [];
            foreach ($accountEntities as $account) {
                $accounts[] = $this->getApiObject($account, $locale);
            }

            return $accounts;
        }

        return;
    }

    /**
     * Returns an api entity for an doctrine entity.
     *
     * @param AccountInterface $account
     * @param string $locale
     *
     * @return null|AccountApi
     */
    public function getAccount($account, $locale)
    {
        if ($account) {
            return $this->getApiObject($account, $locale);
        }

        return;
    }

    public function deleteAllRelations($entity)
    {
        parent::deleteAllRelations($entity);
        $this->deleteBankAccounts($entity);
    }

    /**
     * Deletes (not just removes) all bank-accounts which are assigned to a contact.
     *
     * @param AccountInterface $entity
     *
     * @return void
     */
    public function deleteBankAccounts($entity)
    {
        if ($entity->getBankAccounts()) {
            $this->deleteAllEntitiesOfCollection($entity->getBankAccounts());
        }
    }

    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = [])
    {
        $entities = $this->accountRepository->findByFilters($filters, $page, $pageSize, $limit, $locale, $options);

        return \array_map(
            function($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $entities
        );
    }

    /**
     * Takes a account entity and a locale and returns the api object.
     *
     * @param AccountInterface $account
     * @param string $locale
     *
     * @return AccountApi
     */
    protected function getApiObject($account, $locale)
    {
        $apiObject = $this->accountFactory->createApiEntity($account, $locale);
        if ($account->getLogo()) {
            $apiLogo = $this->mediaManager->getById($account->getLogo()->getId(), $locale);
            $apiObject->setLogo($apiLogo);
        }

        return $apiObject;
    }
}
