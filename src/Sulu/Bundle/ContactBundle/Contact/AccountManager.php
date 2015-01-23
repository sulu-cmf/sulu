<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

/**
 * This Manager handles Account functionality
 * Class AccountManager
 *
 * @package Sulu\Bundle\ContactBundle\Contact
 */
class AccountManager extends AbstractContactManager
{
    protected $accountEntity = 'SuluContactBundle:Account';
    protected $contactEntity = 'SuluContactBundle:Contact';
    protected $addressEntity = 'SuluContactBundle:Address';
    protected $tagManager;

    public function __construct(ObjectManager $em, TagmanagerInterface $tagManager)
    {
        parent::__construct($em);
        $this->tagManager = $tagManager;
    }

    /**
     * adds an address to the entity
     *
     * @param Account $account The entity to add the address to
     * @param AddressEntity $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return AccountAddressEntity
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
        $account->addAccountAddresse($accountAddress);
        $address->addAccountAddresse($accountAddress);
        $this->em->persist($accountAddress);

        return $accountAddress;
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     *
     * @param AccountEntity $account
     * @param AccountAddressEntity $accountAddress
     * @return mixed|void
     * @throws \Exception
     */
    public function removeAddressRelation($account, $accountAddress)
    {
        if (!$account || !$accountAddress) {
            throw new \Exception('Account and AccountAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var AddressEntity $address */
        $address = $accountAddress->getAddress();
        $address = $this->em->getRepository(
            'SuluContactBundle:Address'
        )->findById($address->getId());

        $isMain = $accountAddress->getMain();

        // remove relation
        $address->removeAccountAddresse($accountAddress);
        $account->removeAccountAddresse($accountAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($account->getAccountContacts());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($accountAddress);
    }

    /**
     * Returns a collection of relations to get addresses
     *
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity)
    {
        return $entity->getAccountAddresses();
    }

    /**
     * Gets account by id
     * @param $id
     * @param $locale
     * @throws EntityNotFoundException
     * @return mixed
     */
    public function getById($id, $locale)
    {
        $account = $this->em->getRepository($this->accountEntity)->findAccountById($id);
        if (!$account) {
            throw new EntityNotFoundException($this->accountEntity, $id);
        }

        return new Account($account, $locale, $this->tagManager);
    }

    /**
     * Gets account by id - can include relations
     * @param $id
     * @param $locale
     * @param $includes
     * @return Account
     * @throws EntityNotFoundException
     */
    public function getByIdAndInclude($id, $locale, $includes)
    {
        $account = $this->em
            ->getRepository($this->accountEntity)
            ->findAccountById($id, in_array('contacts', $includes));

        if (!$account) {
            throw new EntityNotFoundException($this->accountEntity, $id);
        }
        $acc = new Account($account, $locale, $this->tagManager);

        return $acc;
    }

    /**
     * Returns contacts by account id
     *
     * @param $id
     * @param $locale
     * @return array
     */
    public function findContactsByAccountId($id, $locale, $onlyFetchMainAccounts = false)
    {
        $contactsEntities = $this->em->getRepository($this->contactEntity)->findByAccountId($id, null, false, $onlyFetchMainAccounts);
        $contacts = [];
        if ($contactsEntities) {
            foreach ($contactsEntities as $contact) {
                $contacts[] = new Contact($contact, $locale, $this->tagManager);
            }

            return $contacts;
        } else {
            return null;
        }
    }

    /**
     * Returns all accounts
     * @param $locale
     * @param $filter
     * @return null
     */
    public function findAll($locale, $filter = null)
    {
        if ($filter) {
            $accountEntities = $this->em->getRepository($this->accountEntity)->findByFilter($filter);
        } else {
            $accountEntities = $this->em->getRepository($this->accountEntity)->findAll();
        }
        $accounts = [];
        if ($accountEntities) {
            foreach ($accountEntities as $account) {
                $accounts[] = new Account($account, $locale, $this->tagManager);
            }
        } else {
            return null;
        }
        return $accounts;
    }

    /**
     * Returns an api entity for an doctrine entity
     * @param $account
     * @param $locale
     * @return null|Account
     */
    public function getAccount($account, $locale)
    {
        if ($account) {
            return new Account($account, $locale, $this->tagManager);
        } else {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllRelations($entity)
    {
        parent::deleteAllRelations($entity);
        // add bank-accounts for accounts
        $this->deleteBankAccounts($entity);
    }

    /**
     * deletes (not just removes) all bank-accounts which are assigned to a contact
     * @param $entity
     */
    public function deleteBankAccounts($entity)
    {
        /** @var Account $entity */
        if ($entity->getBankAccounts()) {
            $this->deleteAllEntitiesOfCollection($entity->getBankAccounts());
        }
    }
}
