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
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

/**
 * TODO: https://github.com/sulu-io/sulu/pull/1171
 * This class needs to be refactored since in the first
 * iteration the logic was just moved from the Controller to this class due
 * to better reusability. Reduce complexity of this service by splitting it
 * into multiple services!
 *
 * @template DoctrineEntity
 * @template ApiEntity
 * @template AddressRelationEntity
 *
 * @implements ContactManagerInterface<DoctrineEntity, ApiEntity, AddressRelationEntity>
 */
abstract class AbstractContactManager implements ContactManagerInterface
{
    use RelationTrait;

    protected static $accountContactEntityName = AccountContact::class;

    protected static $positionEntityName = Position::class;

    protected static $addressTypeEntityName = AddressType::class;

    protected static $urlTypeEntityName = UrlType::class;

    protected static $emailTypeEntityName = EmailType::class;

    protected static $faxTypeEntityName = FaxType::class;

    protected static $socialMediaProfileTypeEntityName = SocialMediaProfileType::class;

    protected static $phoneTypeEntityName = PhoneType::class;

    protected static $addressEntityName = Address::class;

    protected static $emailEntityName = Email::class;

    protected static $urlEntityName = Url::class;

    protected static $phoneEntityName = Phone::class;

    protected static $categoryEntityName = CategoryInterface::class;

    public function __construct(
        protected ObjectManager $em,
        protected TagManagerInterface $tagManager,
        protected MediaManagerInterface $mediaManager,
    ) {
    }

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection.
     *
     * @param Collection<int, AddressRelationEntity> $arrayCollection
     *
     * @return bool returns true if a element was unset
     */
    public function unsetMain($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->forAll(
                function($index, $entry) {
                    if (true === $entry->getMain()) {
                        $entry->setMain(false);

                        return false;
                    }

                    return true;
                }
            );
        }

        return false;
    }

    /**
     * sets the first element to main, if none is set.
     *
     * @param Collection<int, AddressRelationEntity>|Collection<int, AccountContact> $arrayCollection
     */
    public function setMainForCollection($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty() && !$this->hasMain($arrayCollection)) {
            $firstElement = $arrayCollection->first();
            if ($firstElement) {
                $firstElement->setMain(true);
            }
        }
    }

    /**
     * checks if a collection for main attribute.
     *
     * @param Collection<int, AddressRelationEntity>|Collection<int, AccountContact> $arrayCollection
     * @param mixed $mainEntity will be set, if found
     *
     * @return bool
     */
    private function hasMain($arrayCollection, &$mainEntity = null)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->exists(
                function($index, $entity) {
                    $mainEntity = $entity;

                    return true === $entity->getMain();
                }
            );
        }

        return false;
    }

    /**
     * sets Entity's Main-Email.
     *
     * @param DoctrineEntity $entity
     */
    public function setMainEmail($entity)
    {
        // set main to first entry or to null
        if ($entity->getEmails()->isEmpty()) {
            $entity->setMainEmail(null);
        } else {
            $entity->setMainEmail($entity->getEmails()->first()->getEmail());
        }
    }

    /**
     * sets Entity's Main-Phone.
     *
     * @param DoctrineEntity $entity
     */
    public function setMainPhone($entity)
    {
        // set main to first entry or to null
        if ($entity->getPhones()->isEmpty()) {
            $entity->setMainPhone(null);
        } else {
            $entity->setMainPhone($entity->getPhones()->first()->getPhone());
        }
    }

    /**
     * sets Entity's Main-Fax.
     *
     * @param DoctrineEntity $entity
     */
    public function setMainFax($entity)
    {
        // set main to first entry or to null
        if ($entity->getFaxes()->isEmpty()) {
            $entity->setMainFax(null);
        } else {
            $entity->setMainFax($entity->getFaxes()->first()->getFax());
        }
    }

    /**
     * sets Entity's Main-Url.
     *
     * @param DoctrineEntity $entity
     */
    public function setMainUrl($entity)
    {
        // set main to first entry or to null
        if ($entity->getUrls()->isEmpty()) {
            $entity->setMainUrl(null);
        } else {
            $entity->setMainUrl($entity->getUrls()->first()->getUrl());
        }
    }

    /**
     * Returns AccountContact relation if exists.
     *
     * @return AccountContact|null
     */
    public function getAccounContact(AccountInterface $account, ContactInterface $contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            /** @var AccountContact $accountContact */
            if ($accountContact->getAccount() === $account) {
                return $accountContact;
            }
        }

        return null;
    }

    /**
     * returns the main account-contact relation.
     *
     * @param DoctrineEntity $contact
     *
     * @return AccountContact|false
     */
    public function getMainAccountContact($contact)
    {
        foreach ($contact->getAccountContacts() as $accountContact) {
            /** @var AccountContact $accountContact */
            if ($accountContact->getMain()) {
                return $accountContact;
            }
        }

        return false;
    }

    /**
     * creates a new main Account Contacts relation.
     *
     * @param Position $position
     *
     * @return AccountContact
     */
    public function createMainAccountContact(ContactInterface $contact, AccountInterface $account, $position = null)
    {
        $accountContact = new AccountContact();
        $accountContact->setAccount($account);
        $accountContact->setContact($contact);
        $accountContact->setMain(true);
        $this->em->persist($accountContact);
        $contact->addAccountContact($accountContact);
        $accountContact->setPosition($position);

        return $accountContact;
    }

    /**
     * return address type by name.
     *
     * @param string $name
     *
     * @return AddressType
     */
    public function getAddressTypeByName($name)
    {
        return $this->em->getRepository(self::$addressTypeEntityName)->findOneByName($name);
    }

    /**
     * return url type by name.
     *
     * @param string $name
     *
     * @return UrlType
     */
    public function getUrlTypeByName($name)
    {
        return $this->em->getRepository(self::$urlTypeEntityName)->findOneByName($name);
    }

    /**
     * return phone type by name.
     *
     * @param string $name
     *
     * @return PhoneType
     */
    public function getPhoneTypeByName($name)
    {
        return $this->em->getRepository(self::$phoneTypeEntityName)->findOneByName($name);
    }

    /**
     * return fax type by name.
     *
     * @param string $name
     *
     * @return FaxType
     */
    public function getFaxTypeByName($name)
    {
        return $this->em->getRepository(self::$faxTypeEntityName)->findOneByName($name);
    }

    /**
     * Return social media profile type by name.
     *
     * @param string $name
     *
     * @return SocialMediaProfileType
     */
    public function getSocialMediaProfileTypeByName($name)
    {
        return $this->em->getRepository(self::$socialMediaProfileTypeEntityName)->findOneByName($name);
    }

    /**
     * return email type by name.
     *
     * @param string $name
     *
     * @return EmailType
     */
    public function getEmailTypeByName($name)
    {
        return $this->em->getRepository(self::$emailTypeEntityName)->findOneByName($name);
    }

    /**
     * clears all relational data from entity and deletes it.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteAllRelations($entity)
    {
        $this->deleteNotes($entity);
        $this->deleteAddresses($entity);
        $this->deleteEmails($entity);
        $this->deleteFaxes($entity);
        $this->deleteSocialMediaProfiles($entity);
        $this->deletePhones($entity);
        $this->deleteUrls($entity);
    }

    /**
     * deletes all notes that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteNotes($entity)
    {
        if ($entity->getNotes()) {
            $this->deleteAllEntitiesOfCollection($entity->getNotes());
        }
    }

    /**
     * deletes all phones that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deletePhones($entity)
    {
        if ($entity->getPhones()) {
            $this->deleteAllEntitiesOfCollection($entity->getPhones());
        }
    }

    /**
     * deletes all faxes that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteFaxes($entity)
    {
        if ($entity->getFaxes()) {
            $this->deleteAllEntitiesOfCollection($entity->getFaxes());
        }
    }

    /**
     * Deletes all social media profiles that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteSocialMediaProfiles($entity)
    {
        if ($entity->getSocialMediaProfiles()) {
            $this->deleteAllEntitiesOfCollection($entity->getSocialMediaProfiles());
        }
    }

    /**
     * deletes all urls that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteUrls($entity)
    {
        if ($entity->getUrls()) {
            $this->deleteAllEntitiesOfCollection($entity->getUrls());
        }
    }

    /**
     * deletes all addresses that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteAddresses($entity)
    {
        // clear addresses
        if ($entity->getAccountAddresses()) {
            foreach ($entity->getAccountAddresses() as $accountAddresses) {
                $this->em->remove($accountAddresses->getAddress());
                $this->em->remove($accountAddresses);
            }
        }
    }

    /**
     * deletes all emails that are assigned to entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function deleteEmails($entity)
    {
        if ($entity->getEmails()) {
            $this->deleteAllEntitiesOfCollection($entity->getEmails());
        }
    }

    /**
     * @param Collection $arrayCollection
     *
     * @return void
     */
    protected function deleteAllEntitiesOfCollection($arrayCollection)
    {
        foreach ($arrayCollection as $entity) {
            $this->em->remove($entity);
        }
    }

    /**
     * Returns the billing address of an account/contact.
     *
     * @param DoctrineEntity $entity
     * @param bool $force Forces function to return an address if any address is defined
     *                    if no delivery address is defined it will first return the main address then any
     */
    public function getBillingAddress($entity, $force = false)
    {
        /* @var Address $address */
        $conditionCallback = function($address) {
            return $address->getBillingAddress();
        };

        return $this->getAddressByCondition($entity, $conditionCallback, $force);
    }

    /**
     * Returns the delivery address.
     *
     * @param DoctrineEntity $entity
     * @param bool $force Forces function to return an address if any address is defined
     *                    if no delivery address is defined it will first return the main address then any
     */
    public function getDeliveryAddress($entity, $force = false)
    {
        /* @var Address $address */
        $conditionCallback = function($address) {
            return $address->getDeliveryAddress();
        };

        return $this->getAddressByCondition($entity, $conditionCallback, $force);
    }

    /**
     * checks if an account is employee of a company.
     *
     * @param ContactInterface $contact
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function contactIsEmployeeOfAccount($contact, $account)
    {
        if ($contact->getAccountContacts() && !$contact->getAccountContacts()->isEmpty()) {
            foreach ($contact->getAccountContacts() as $accountContact) {
                if ($accountContact->getAccount()->getId() === $account->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * returns addresses from account or contact.
     *
     * @param DoctrineEntity $entity
     *
     * @return Collection<int, ContactAddress|AccountAddress>|null
     */
    private function getAddresses($entity)
    {
        if ($entity instanceof AccountInterface) {
            return $entity->getAccountAddresses();
        } elseif ($entity instanceof ContactInterface) {
            return $entity->getContactAddresses();
        }

        return null;
    }

    /**
     * Returns an address by callback-condition.
     *
     * @param DoctrineEntity $entity
     * @param bool $force Forces function to return an address if any address is defined
     *                    if no delivery address is defined it will first return the main address then any
     *
     * @return Address|null
     */
    public function getAddressByCondition($entity, callable $conditionCallback, $force = false)
    {
        $addresses = $this->getAddresses($entity);
        $address = null;
        $main = null;

        if (!\is_null($addresses)) {
            /* @var AccountAddress $accountAddress */
            foreach ($addresses as $address) {
                if ($conditionCallback($address->getAddress())) {
                    return $address->getAddress();
                }
                if ($address->getMain()) {
                    $main = $address->getAddress();
                }
            }
            if ($force) {
                // return main or first address
                if (null === $main && $addresses->first()) {
                    return $addresses->first()->getAddress();
                }
            }
        }

        return $main;
    }

    /**
     * adds new relations.
     *
     * @param DoctrineEntity $contact
     * @param array $data
     *
     * @return void
     */
    public function addNewContactRelations($contact, $data)
    {
        $contactDetailsData = $this->getProperty($data, 'contactDetails') ?? [];

        // urls
        $urls = $this->getProperty($contactDetailsData, 'websites');
        if (!empty($urls)) {
            foreach ($urls as $urlData) {
                if (!empty($urlData['website'])) {
                    $this->addUrl($contact, $urlData);
                }
            }
            $this->setMainUrl($contact);
        }

        //faxes
        $faxes = $this->getProperty($contactDetailsData, 'faxes');
        if (!empty($faxes)) {
            foreach ($faxes as $faxData) {
                if (!empty($faxData['fax'])) {
                    $this->addFax($contact, $faxData);
                }
            }
            $this->setMainFax($contact);
        }

        // Social media profiles.
        $socialMediaProfiles = $this->getProperty($contactDetailsData, 'socialMedia');
        if (!empty($socialMediaProfiles)) {
            foreach ($socialMediaProfiles as $socialMediaProfileData) {
                $this->addSocialMediaProfile($contact, $socialMediaProfileData);
            }
        }

        // emails
        $emails = $this->getProperty($contactDetailsData, 'emails');
        if (!empty($emails)) {
            foreach ($emails as $emailData) {
                if (!empty($emailData['email'])) {
                    $this->addEmail($contact, $emailData);
                }
            }
            $this->setMainEmail($contact);
        }

        // phones
        $phones = $this->getProperty($contactDetailsData, 'phones');
        if (!empty($phones)) {
            foreach ($phones as $phoneData) {
                if (!empty($phoneData['phone'])) {
                    $this->addPhone($contact, $phoneData);
                }
            }
            $this->setMainPhone($contact);
        }

        // addresses
        $addresses = $this->getProperty($data, 'addresses');
        if (!empty($addresses)) {
            foreach ($addresses as $addressData) {
                $address = $this->createAddress($addressData, $isMain);
                $this->addAddress($contact, $address, $isMain);
            }
        }
        // set main address (if it was not set yet)
        $this->setMainForCollection($this->getAddressRelations($contact));

        // notes
        $notes = $this->getProperty($data, 'notes');
        if (!empty($notes)) {
            foreach ($notes as $noteData) {
                $this->addNote($contact, $noteData);
            }
        }

        // handle tags
        $tags = $this->getProperty($data, 'tags');
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $this->addTag($contact, $tag);
            }
        }

        // process details
        if (null !== $this->getProperty($data, 'bankAccounts')) {
            $this->processBankAccounts($contact, $this->getProperty($data, 'bankAccounts', []));
        }
    }

    /**
     * Process all emails from request.
     *
     * @param DoctrineEntity $contact The contact on which is worked
     * @param array $emails
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processEmails($contact, $emails)
    {
        $get = function($email) {
            /* @var Email $email */

            return $email->getId();
        };

        $delete = function($email) use ($contact) {
            return $contact->removeEmail($email);
        };

        $update = function($email, $matchedEntry) {
            return $this->updateEmail($email, $matchedEntry);
        };

        $add = function($email) use ($contact) {
            return $this->addEmail($contact, $email);
        };

        $entities = $contact->getEmails();

        $result = $this->processSubEntities(
            $entities,
            \array_filter($emails, function($email) {
                return $email['email'];
            }),
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        // check main
        $this->setMainEmail($contact);

        return $result;
    }

    /**
     * Adds a new email to the given contact and persist it with the given object manager.
     *
     * @param DoctrineEntity $contact
     * @param array $emailData
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addEmail($contact, $emailData)
    {
        $success = true;

        $emailType = $this->em
            ->getRepository(self::$emailTypeEntityName)
            ->find($emailData['emailType']);

        if (isset($emailData['id'])) {
            throw new EntityIdAlreadySetException(self::$emailEntityName, $emailData['id']);
        } elseif (!$emailType) {
            throw new EntityNotFoundException(self::$emailTypeEntityName, $emailData['emailType']);
        } else {
            $email = new Email();
            $email->setEmail($emailData['email']);
            $email->setEmailType($emailType);
            $this->em->persist($email);
            $contact->addEmail($email);
        }

        return $success;
    }

    /**
     * Updates the given email address.
     *
     * @param Email $email The email object to update
     * @param array $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     *
     * @throws EntityNotFoundException
     */
    protected function updateEmail(Email $email, $entry)
    {
        $success = true;

        $emailType = $this->em
            ->getRepository(self::$emailTypeEntityName)
            ->find($entry['emailType']);

        if (!$emailType) {
            throw new EntityNotFoundException(self::$emailTypeEntityName, $entry['emailType']);
        } else {
            $email->setEmail($entry['email']);
            $email->setEmailType($emailType);
        }

        return $success;
    }

    /**
     * Process all urls of request.
     *
     * @param DoctrineEntity $contact The contact to be processed
     * @param array $urls
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processUrls($contact, $urls)
    {
        $get = function($url) {
            return $url->getId();
        };

        $delete = function($url) use ($contact) {
            return $contact->removeUrl($url);
        };

        $update = function($url, $matchedEntry) {
            return $this->updateUrl($url, $matchedEntry);
        };

        $add = function($url) use ($contact) {
            return $this->addUrl($contact, $url);
        };

        $entities = $contact->getUrls();

        $result = $this->processSubEntities(
            $entities,
            \array_filter($urls, function($url) {
                return $url['website'];
            }),
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        // check main
        $this->setMainUrl($contact);

        return $result;
    }

    /**
     * Process all categories of request.
     *
     * @param DoctrineEntity $contact - the contact which is processed
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processCategories($contact, $categoryIds)
    {
        $contact->getCategories()->clear();

        if (!$categoryIds) {
            return true;
        }

        foreach ($categoryIds as $categoryId) {
            $category = $this->em->getRepository(self::$categoryEntityName)->find($categoryId);

            if (!$category) {
                throw new EntityNotFoundException(self::$categoryEntityName, $categoryId);
            }

            $contact->addCategory($category);
        }

        return true;
    }

    /**
     * @param array $entry
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function updateUrl(Url $url, $entry)
    {
        $success = true;

        /** @var UrlType $urlType */
        $urlType = $this->em->getRepository(
            self::$urlTypeEntityName
        )->find($entry['websiteType']);

        if (!$urlType) {
            throw new EntityNotFoundException(self::$urlTypeEntityName, $entry['websiteType']);
        } else {
            $url->setUrl($entry['website']);
            $url->setUrlType($urlType);
        }

        return $success;
    }

    /**
     * Adds a new tag to the given contact.
     *
     * @param DoctrineEntity $contact
     * @param array $data
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addUrl($contact, $data)
    {
        $success = true;

        $urlType = $this->em->getRepository(
            self::$urlTypeEntityName
        )->find($data['websiteType']);

        if (isset($data['id'])) {
            throw new EntityIdAlreadySetException(self::$urlEntityName, $data['id']);
        } elseif (!$urlType) {
            throw new EntityNotFoundException(self::$urlTypeEntityName, $data['websiteType']);
        } else {
            $url = new Url();
            $url->setUrl($data['website']);
            $url->setUrlType($urlType);
            $this->em->persist($url);
            $contact->addUrl($url);
        }

        return $success;
    }

    /**
     * Process all phones from request.
     *
     * @param DoctrineEntity $contact The contact on which is processed
     * @param array $phones
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processPhones($contact, $phones)
    {
        $get = function($phone) {
            return $phone->getId();
        };

        $delete = function($phone) use ($contact) {
            return $contact->removePhone($phone);
        };

        $update = function($phone, $matchedEntry) {
            return $this->updatePhone($phone, $matchedEntry);
        };

        $add = function($phone) use ($contact) {
            return $this->addPhone($contact, $phone);
        };

        $entities = $contact->getPhones();

        $result = $this->processSubEntities(
            $entities,
            \array_filter($phones, function($phone) {
                return $phone['phone'];
            }),
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        // check main
        $this->setMainPhone($contact);

        return $result;
    }

    /**
     * Add a new phone to the given contact and persist it with the given object manager.
     *
     * @param DoctrineEntity $contact
     * @param array $phoneData
     *
     * @return bool True if there was no error, otherwise false
     *
     * @throws EntityNotFoundException
     * @throws EntityIdAlreadySetException
     */
    protected function addPhone($contact, $phoneData)
    {
        $success = true;

        $phoneType = $this->em->getRepository(
            self::$phoneTypeEntityName
        )->find($phoneData['phoneType']);

        if (isset($phoneData['id'])) {
            throw new EntityIdAlreadySetException(self::$phoneEntityName, $phoneData['id']);
        } elseif (!$phoneType) {
            throw new EntityNotFoundException(self::$phoneTypeEntityName, $phoneData['phoneType']);
        } else {
            $phone = new Phone();
            $phone->setPhone($phoneData['phone']);
            $phone->setPhoneType($phoneType);
            $this->em->persist($phone);
            $contact->addPhone($phone);
        }

        return $success;
    }

    /**
     * Updates the given phone.
     *
     * @param Phone $phone The phone object to update
     * @param array $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     *
     * @throws EntityNotFoundException
     */
    protected function updatePhone(Phone $phone, $entry)
    {
        $success = true;

        $phoneType = $this->em->getRepository(
            self::$phoneTypeEntityName
        )->find($entry['phoneType']);

        if (!$phoneType) {
            throw new EntityNotFoundException(self::$phoneTypeEntityName, $entry['phoneType']);
        } else {
            $phone->setPhone($entry['phone']);
            $phone->setPhoneType($phoneType);
        }

        return $success;
    }

    /**
     * @param DoctrineEntity $contact
     * @param array $faxes
     *
     * @return bool
     */
    public function processFaxes($contact, $faxes)
    {
        $get = function($fax) {
            return $fax->getId();
        };

        $delete = function($fax) use ($contact) {
            $contact->removeFax($fax);

            return true;
        };

        $update = function($fax, $matchedEntry) {
            return $this->updateFax($fax, $matchedEntry);
        };

        $add = function($fax) use ($contact) {
            $this->addFax($contact, $fax);

            return true;
        };

        $entities = $contact->getFaxes();

        $result = $this->processSubEntities(
            $entities,
            \array_filter($faxes, function($fax) {
                return $fax['fax'];
            }),
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        // check main
        $this->setMainFax($contact);

        return $result;
    }

    /**
     * @param DoctrineEntity $contact
     * @param array $faxData
     *
     * @return void
     *
     * @throws EntityIdAlreadySetException
     * @throws EntityNotFoundException
     */
    protected function addFax($contact, $faxData)
    {
        $faxEntity = Fax::class;

        $faxType = $this->em
            ->getRepository(self::$faxTypeEntityName)
            ->find($faxData['faxType']);

        if (isset($faxData['id'])) {
            throw new EntityIdAlreadySetException($faxEntity, $faxData['id']);
        } elseif (!$faxType) {
            throw new EntityNotFoundException(self::$faxTypeEntityName, $faxData['faxType']);
        } else {
            $fax = new Fax();
            $fax->setFax($faxData['fax']);
            $fax->setFaxType($faxType);
            $this->em->persist($fax);
            $contact->addFax($fax);
        }
    }

    /**
     * @param array<string, string> $entry
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function updateFax(Fax $fax, $entry)
    {
        $success = true;

        $faxType = $this->em->getRepository(
            self::$faxTypeEntityName
        )->find($entry['faxType']);

        if (!$faxType) {
            throw new EntityNotFoundException(self::$faxTypeEntityName, $entry['faxType']);
        } else {
            $fax->setFax($entry['fax']);
            $fax->setFaxType($faxType);
        }

        return $success;
    }

    /**
     * @param DoctrineEntity $contact
     * @param array $socialMediaProfiles
     *
     * @return bool
     */
    public function processSocialMediaProfiles($contact, $socialMediaProfiles)
    {
        $get = function($socialMediaProfile) {
            return $socialMediaProfile->getId();
        };

        $delete = function($socialMediaProfile) use ($contact) {
            $contact->removeSocialMediaProfile($socialMediaProfile);

            return true;
        };

        $update = function($socialMediaProfile, $matchedEntry) {
            return $this->updateSocialMediaProfile($socialMediaProfile, $matchedEntry);
        };

        $add = function($socialMediaProfile) use ($contact) {
            $this->addSocialMediaProfile($contact, $socialMediaProfile);

            return true;
        };

        $entities = $contact->getSocialMediaProfiles();

        $result = $this->processSubEntities(
            $entities,
            \array_filter($socialMediaProfiles, function($socialMediaProfile) {
                return $socialMediaProfile['username'];
            }),
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * @param DoctrineEntity $contact
     * @param array $socialMediaProfileData
     *
     * @return void
     *
     * @throws EntityIdAlreadySetException
     * @throws EntityNotFoundException
     */
    protected function addSocialMediaProfile($contact, $socialMediaProfileData)
    {
        $socialMediaProfileEntity = SocialMediaProfile::class;

        $socialMediaProfileType = $this->em
            ->getRepository(self::$socialMediaProfileTypeEntityName)
            ->find($socialMediaProfileData['socialMediaType']);

        if (isset($socialMediaProfileData['id'])) {
            throw new EntityIdAlreadySetException($socialMediaProfileEntity, $socialMediaProfileData['id']);
        } elseif (!$socialMediaProfileType) {
            throw new EntityNotFoundException(
                self::$socialMediaProfileTypeEntityName,
                $socialMediaProfileData['socialMediaType']
            );
        }

        $socialMediaProfile = new SocialMediaProfile();
        $socialMediaProfile->setUsername($socialMediaProfileData['username']);
        $socialMediaProfile->setSocialMediaProfileType($socialMediaProfileType);
        $this->em->persist($socialMediaProfile);
        $contact->addSocialMediaProfile($socialMediaProfile);
    }

    /**
     * @param array $entry
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function updateSocialMediaProfile(SocialMediaProfile $socialMediaProfile, $entry)
    {
        $socialMediaProfileType = $this->em->getRepository(
            self::$socialMediaProfileTypeEntityName
        )->find($entry['socialMediaType']);

        if (!$socialMediaProfileType) {
            throw new EntityNotFoundException(
                self::$socialMediaProfileTypeEntityName,
                $entry['socialMediaType']
            );
        }

        $socialMediaProfile->setUsername($entry['username']);
        $socialMediaProfile->setSocialMediaProfileType($socialMediaProfileType);

        return true;
    }

    /**
     * Creates an address based on the data passed.
     *
     * @param array $addressData
     * @param bool $isMain returns if address is main address
     *
     * @return Address
     *
     * @throws EntityIdAlreadySetException
     * @throws EntityNotFoundException
     */
    protected function createAddress($addressData, &$isMain = null)
    {
        $addressType = $this->em
            ->getRepository(self::$addressTypeEntityName)
            ->find($addressData['addressType']);

        if (isset($addressData['id'])) {
            throw new EntityIdAlreadySetException(self::$addressEntityName, $addressData['id']);
        } elseif (!$addressType) {
            throw new EntityNotFoundException(self::$addressTypeEntityName, $addressData['addressType']);
        } else {
            $address = new Address();

            if (isset($addressData['street'])) {
                $address->setStreet($addressData['street']);
            }
            if (isset($addressData['number'])) {
                $address->setNumber($addressData['number']);
            }
            if (isset($addressData['zip'])) {
                $address->setZip($addressData['zip']);
            }
            if (isset($addressData['city'])) {
                $address->setCity($addressData['city']);
            }
            if (isset($addressData['state'])) {
                $address->setState($addressData['state']);
            }
            if (isset($addressData['countryCode'])) {
                $address->setCountryCode($addressData['countryCode']);
            }
            if (isset($addressData['latitude'])) {
                $address->setLatitude('' !== $addressData['latitude'] ? $addressData['latitude'] : null);
            }
            if (isset($addressData['longitude'])) {
                $address->setLongitude('' !== $addressData['longitude'] ? $addressData['longitude'] : null);
            }
            if (isset($addressData['note'])) {
                $address->setNote($addressData['note']);
            }
            if (isset($addressData['title'])) {
                $address->setTitle($addressData['title']);
            }

            $isMain = false;
            if (isset($addressData['primaryAddress'])) {
                $isMain = $this->getBooleanValue($addressData['primaryAddress']);
            }

            if (isset($addressData['billingAddress'])) {
                $address->setBillingAddress($this->getBooleanValue($addressData['billingAddress']));
            }
            if (isset($addressData['deliveryAddress'])) {
                $address->setDeliveryAddress($this->getBooleanValue($addressData['deliveryAddress']));
            }
            if (isset($addressData['postboxCity'])) {
                $address->setPostboxCity($addressData['postboxCity']);
            }
            if (isset($addressData['postboxNumber'])) {
                $address->setPostboxNumber($addressData['postboxNumber']);
            }
            if (isset($addressData['postboxPostcode'])) {
                $address->setPostboxPostcode($addressData['postboxPostcode']);
            }

            $address->setAddressType($addressType);

            // add additional fields
            if (isset($addressData['addition'])) {
                $address->setAddition($addressData['addition']);
            }

            $this->em->persist($address);
        }

        return $address;
    }

    /**
     * Updates the given address.
     *
     * @param Address $address The phone object to update
     * @param mixed $entry The entry with the new data
     * @param bool $isMain returns if address should be set to main
     *
     * @return bool True if successful, otherwise false
     *
     * @throws EntityNotFoundException
     */
    protected function updateAddress(Address $address, $entry, &$isMain = null)
    {
        $success = true;

        $addressType = $this->em
            ->getRepository(self::$addressTypeEntityName)
            ->find($entry['addressType']);

        if (!$addressType) {
            throw new EntityNotFoundException(self::$addressTypeEntityName, $entry['addressType']);
        }

        if (isset($entry['street'])) {
            $address->setStreet($entry['street']);
        }
        if (isset($entry['number'])) {
            $address->setNumber($entry['number']);
        }
        if (isset($entry['zip'])) {
            $address->setZip($entry['zip']);
        }
        if (isset($entry['city'])) {
            $address->setCity($entry['city']);
        }
        if (isset($entry['state'])) {
            $address->setState($entry['state']);
        }
        if (isset($entry['countryCode'])) {
            $address->setCountryCode($entry['countryCode']);
        }
        if ($addressType) {
            $address->setAddressType($addressType);
        }
        if (isset($entry['latitude'])) {
            $address->setLatitude($entry['latitude'] ?: null);
        }
        if (isset($entry['longitude'])) {
            $address->setLongitude($entry['longitude'] ?: null);
        }
        if (isset($entry['note'])) {
            $address->setNote($entry['note']);
        }
        if (isset($entry['title'])) {
            $address->setTitle($entry['title']);
        }
        if (isset($entry['primaryAddress'])) {
            $isMain = $this->getBooleanValue($entry['primaryAddress']);
        } else {
            $isMain = false;
        }
        if (isset($entry['billingAddress'])) {
            $address->setBillingAddress($this->getBooleanValue($entry['billingAddress']));
        }
        if (isset($entry['deliveryAddress'])) {
            $address->setDeliveryAddress($this->getBooleanValue($entry['deliveryAddress']));
        }
        if (isset($entry['postboxCity'])) {
            $address->setPostboxCity($entry['postboxCity']);
        }
        if (isset($entry['postboxNumber'])) {
            $address->setPostboxNumber($entry['postboxNumber']);
        }
        if (isset($entry['postboxPostcode'])) {
            $address->setPostboxPostcode($entry['postboxPostcode']);
        }
        if (isset($entry['addition'])) {
            $address->setAddition($entry['addition']);
        }

        return $success;
    }

    /**
     * Checks if a value is a boolean and converts it if necessary and returns it.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function getBooleanValue($value)
    {
        if (\is_string($value)) {
            return 'true' === $value ? true : false;
        } elseif (\is_bool($value)) {
            return $value;
        } elseif (\is_numeric($value)) {
            return 1 === $value ? true : false;
        }

        return false;
    }

    /**
     * Process all notes from request.
     *
     * @param DoctrineEntity $contact The contact on which is worked
     * @param array $notes
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processNotes($contact, $notes)
    {
        $get = function($note) {
            return $note->getId();
        };

        $delete = function($note) use ($contact) {
            $contact->removeNote($note);

            return true;
        };

        $update = function($note, $matchedEntry) {
            return $this->updateNote($note, $matchedEntry);
        };

        $add = function($note) use ($contact) {
            return $this->addNote($contact, $note);
        };

        $entities = $contact->getNotes();

        $result = $this->processSubEntities(
            $entities,
            $notes,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager.
     *
     * @param DoctrineEntity $contact
     * @param array $noteData
     *
     * @return bool True if there was no error, otherwise false
     *
     * @throws EntityIdAlreadySetException
     */
    protected function addNote($contact, $noteData)
    {
        $noteEntity = Note::class;

        if (isset($noteData['id'])) {
            throw new EntityIdAlreadySetException($noteEntity, $noteData['id']);
        } else {
            $note = new Note();
            $note->setValue($noteData['value']);

            $this->em->persist($note);
            $contact->addNote($note);
        }

        return true;
    }

    /**
     * Updates the given note.
     *
     * @param array $entry The entry with the new data
     *
     * @return bool True if successful, otherwise false
     */
    protected function updateNote(Note $note, $entry)
    {
        $success = true;

        $note->setValue($entry['value']);

        return $success;
    }

    /**
     * Process all tags of request.
     *
     * @param DoctrineEntity $contact The contact on which is worked
     * @param array $tags
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processTags($contact, $tags)
    {
        $get = function($tag) {
            return $tag->getId();
        };

        $delete = function($tag) use ($contact) {
            return $contact->removeTag($tag);
        };

        $update = function() {
            return true;
        };

        $add = function($tag) use ($contact) {
            return $this->addTag($contact, $tag);
        };

        $entities = $contact->getTags();

        $result = $this->processSubEntities(
            $entities,
            $tags,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Adds a new tag to the given contact and persist it with the given object manager.
     *
     * @param DoctrineEntity $contact
     * @param string $data
     *
     * @return bool True if there was no error, otherwise false
     */
    protected function addTag($contact, $data)
    {
        $success = true;
        $resolvedTag = $this->getTagManager()->findOrCreateByName($data);
        $contact->addTag($resolvedTag);

        return $success;
    }

    /**
     * Process all bankAccounts of a request.
     *
     * @param DoctrineEntity $contact
     * @param array $bankAccounts
     *
     * @return bool True if the processing was sucessful, otherwise false
     */
    public function processBankAccounts($contact, $bankAccounts)
    {
        $get = function($bankAccount) {
            return $bankAccount->getId();
        };

        $delete = function($bankAccounts) use ($contact) {
            $contact->removeBankAccount($bankAccounts);

            return true;
        };

        $update = function($bankAccounts, $matchedEntry) {
            return $this->updateBankAccount($bankAccounts, $matchedEntry);
        };

        $add = function($bankAccounts) use ($contact) {
            return $this->addBankAccount($contact, $bankAccounts);
        };

        $entities = $contact->getBankAccounts();

        $result = $this->processSubEntities(
            $entities,
            $bankAccounts,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager.
     *
     * @param DoctrineEntity $contact
     * @param array $data
     *
     * @return bool
     *
     * @throws EntityIdAlreadySetException
     */
    protected function addBankAccount($contact, $data = null)
    {
        $entityName = BankAccount::class;

        if (isset($data['id'])) {
            throw new EntityIdAlreadySetException($entityName, $data['id']);
        }

        $entity = new BankAccount();
        if (isset($data['bic'])) {
            $entity->setBic($data['bic']);
        }
        $entity->setIban($data['iban']);
        $entity->setPublic($this->getBooleanValue(\array_key_exists('public', $data) ? $data['public'] : false));
        if (isset($data['bankName'])) {
            $entity->setBankName($data['bankName']);
        }

        $this->em->persist($entity);
        $contact->addBankAccount($entity);

        return true;
    }

    /**
     * Updates the given note.
     *
     * @param BankAccount $entity The phone object to update
     * @param string $data The entry with the new data
     *
     * @return bool True if successful, otherwise false
     */
    protected function updateBankAccount(BankAccount $entity, $data)
    {
        $success = true;

        if (isset($data['bic'])) {
            $entity->setBic($data['bic']);
        }
        $entity->setIban($data['iban']);
        $entity->setPublic($this->getBooleanValue(\array_key_exists('public', $data) ? $data['public'] : false));
        if (isset($data['bankName'])) {
            $entity->setBankName($data['bankName']);
        }

        return $success;
    }

    /**
     * Process all addresses from request.
     *
     * @param DoctrineEntity $contact The contact on which is worked
     * @param array $addresses
     *
     * @return bool True if the processing was sucessful, otherwise false
     */
    public function processAddresses($contact, $addresses)
    {
        $getAddressId = function($addressRelation) {
            return $addressRelation->getAddress()->getId();
        };

        $delete = function($addressRelation) use ($contact) {
            $this->removeAddressRelation($contact, $addressRelation);

            return true;
        };

        $update = function($addressRelation, $matchedEntry) use ($contact) {
            $address = $addressRelation->getAddress();
            $result = $this->updateAddress($address, $matchedEntry, $isMain);
            if ($isMain) {
                $this->unsetMain($this->getAddressRelations($contact));
            }
            $addressRelation->setMain($isMain);

            return $result;
        };

        $add = function($addressData) use ($contact) {
            $address = $this->createAddress($addressData, $isMain);
            $this->addAddress($contact, $address, $isMain);

            return true;
        };

        $entities = $this->getAddressRelations($contact);

        $result = $this->processSubEntities(
            $entities,
            $addresses,
            $getAddressId,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        // check if main exists, else take first address
        $this->checkAndSetMainAddress($this->getAddressRelations($contact));

        return $result;
    }

    /**
     * Sets main address.
     *
     * @param Collection<int, AddressRelationEntity> $addresses
     *
     * @return void
     */
    protected function checkAndSetMainAddress($addresses)
    {
        $this->setMainForCollection($addresses);
    }

    /**
     * Return property for key or given default value.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        if (\array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * TODO: this is just a hack to avoid relations that start with index != 0
     * otherwise deserialization process will parse relations as object instead of an array
     * reindex entities.
     *
     * @param Collection $entities
     *
     * @return Collection
     */
    private function resetIndexOfSubentites($entities)
    {
        if ($entities && \count($entities) > 0 && \method_exists($entities, 'getValues')) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $entities;
    }

    /**
     * Returns the tag manager.
     *
     * @return TagManagerInterface
     */
    public function getTagManager()
    {
        return $this->tagManager;
    }
}
