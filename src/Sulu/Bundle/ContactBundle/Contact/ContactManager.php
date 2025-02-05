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

use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\PageBundle\Content\Types\Email;
use Sulu\Bundle\PageBundle\Content\Types\Phone;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserRemovedEvent;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * @extends AbstractContactManager<ContactInterface, ContactApi, ContactAddress>
 */
class ContactManager extends AbstractContactManager implements DataProviderRepositoryInterface
{
    public function __construct(
        ObjectManager $em,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        private AccountRepositoryInterface $accountRepository,
        private ContactTitleRepository $contactTitleRepository,
        private ContactRepository $contactRepository,
        protected MediaRepositoryInterface $mediaRepository,
        protected DomainEventCollectorInterface $domainEventCollector,
        protected UserRepository $userRepository,
        private ?TrashManagerInterface $trashManager
    ) {
        parent::__construct($em, $tagManager, $mediaManager);
    }

    /**
     * Find a contact by it's id.
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function findById($id)
    {
        $contact = $this->contactRepository->findById($id);
        if (!$contact) {
            return;
        }

        return $contact;
    }

    /**
     * Returns contact entities by ids.
     *
     * @param array $ids
     * @param string $locale
     */
    public function getByIds($ids, $locale)
    {
        if (!\is_array($ids) || 0 === \count($ids)) {
            return [];
        }

        $contacts = $this->contactRepository->findByIds($ids);

        return \array_map(
            function($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $contacts
        );
    }

    /**
     * Deletes the contact for the given id.
     *
     * @return \Closure
     */
    public function delete()
    {
        /*
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller
         * to this class due to better reusability.
         */
        $delete = function($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findByIdAndDelete($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
            }

            if ($this->trashManager) {
                $this->trashManager->store(ContactInterface::RESOURCE_KEY, $contact);
            }

            $contactId = $contact->getId();
            $contactFullName = $contact->getFullName();

            $addresses = $contact->getAddresses();
            /** @var Address $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $this->em->remove($address);
                }
            }

            $phones = $contact->getPhones()->toArray();
            /** @var Phone $phone */
            foreach ($phones as $phone) {
                if (0 == $phone->getAccounts()->count() && 1 == $phone->getContacts()->count()) {
                    $this->em->remove($phone);
                }
            }
            $emails = $contact->getEmails()->toArray();
            /** @var Email $email */
            foreach ($emails as $email) {
                if (0 == $email->getAccounts()->count() && 1 == $email->getContacts()->count()) {
                    $this->em->remove($email);
                }
            }

            $urls = $contact->getUrls()->toArray();
            /** @var Url $url */
            foreach ($urls as $url) {
                if (0 == $url->getAccounts()->count() && 1 == $url->getContacts()->count()) {
                    $this->em->remove($url);
                }
            }

            $faxes = $contact->getFaxes()->toArray();
            /** @var Fax $fax */
            foreach ($faxes as $fax) {
                if (0 == $fax->getAccounts()->count() && 1 == $fax->getContacts()->count()) {
                    $this->em->remove($fax);
                }
            }

            $socialMediaProfiles = $contact->getSocialMediaProfiles()->toArray();
            /** @var SocialMediaProfile $socialMediaProfile */
            foreach ($socialMediaProfiles as $socialMediaProfile) {
                if (0 == $socialMediaProfile->getAccounts()->count()
                    && 1 == $socialMediaProfile->getContacts()->count()
                ) {
                    $this->em->remove($socialMediaProfile);
                }
            }

            $notes = $contact->getNotes()->toArray();
            /** @var Note $note */
            foreach ($notes as $note) {
                if (0 == $note->getAccounts()->count() && 1 == $note->getContacts()->count()) {
                    $this->em->remove($note);
                }
            }

            $this->em->remove($contact);

            $this->domainEventCollector->collect(
                new ContactRemovedEvent($contactId, $contactFullName)
            );

            /** @var UserInterface|null $user */
            $user = $this->userRepository->findUserByContact($contact->getId());
            if ($user) {
                $this->domainEventCollector->collect(new UserRemovedEvent($user->getId(), $user->getUserIdentifier()));
            }

            $this->em->flush();
        };

        return $delete;
    }

    /**
     * Creates a new contact for the given data.
     *
     * @param array $data
     * @param int|null $id
     * @param bool $patch
     * @param bool $flush
     *
     * @return ContactInterface
     *
     * @throws EntityNotFoundException
     */
    public function save(
        $data,
        $id = null,
        $patch = false,
        $flush = true
    ) {
        /*
         * TODO: https://github.com/sulu-io/sulu/pull/1171
         * This method needs to be refactored since in the first
         * iteration the logic was just moved from the Controller to this class due
         * to better reusability.
         */

        $contactDetailsData = $this->getProperty($data, 'contactDetails', []);
        $isNewContact = false;

        $contactModified = false;
        if ($id) {
            /** @var Contact $contact */
            $contact = $this->contactRepository->findById($id);

            if (!$contact) {
                throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
            }
            if (!$patch || $this->getProperty($data, 'account')) {
                $this->setMainAccount($contact, $data);
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($contactDetailsData, 'emails')) {
                $this->processEmails($contact, $this->getProperty($contactDetailsData, 'emails', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($contactDetailsData, 'phones')) {
                $this->processPhones($contact, $this->getProperty($contactDetailsData, 'phones', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($data, 'addresses')) {
                $this->processAddresses($contact, $this->getProperty($data, 'addresses', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($data, 'notes')) {
                $this->processNotes($contact, $this->getProperty($data, 'notes', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($contactDetailsData, 'faxes')) {
                $this->processFaxes($contact, $this->getProperty($contactDetailsData, 'faxes', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($contactDetailsData, 'socialMedia')) {
                $this->processSocialMediaProfiles(
                    $contact,
                    $this->getProperty($contactDetailsData, 'socialMedia', [])
                );
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($data, 'tags')) {
                $this->processTags($contact, $this->getProperty($data, 'tags', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($contactDetailsData, 'websites')) {
                $this->processUrls($contact, $this->getProperty($contactDetailsData, 'websites', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($data, 'categories')) {
                $this->processCategories($contact, $this->getProperty($data, 'categories', []));
                $contactModified = true;
            }
            if (!$patch || $this->getProperty($data, 'bankAccounts')) {
                $this->processBankAccounts($contact, $this->getProperty($data, 'bankAccounts', []));
                $contactModified = true;
            }
        } else {
            $contact = $this->contactRepository->createNew();
            $isNewContact = true;
        }

        if (!$patch || null !== $this->getProperty($data, 'firstName')) {
            $contact->setFirstName($this->getProperty($data, 'firstName'));
            $contactModified = true;
        }
        if (!$patch || null !== $this->getProperty($data, 'lastName')) {
            $contact->setLastName($this->getProperty($data, 'lastName'));
            $contactModified = true;
        }
        if (!$patch || null !== $this->getProperty($data, 'avatar')) {
            $this->setAvatar($contact, $this->getProperty($data, 'avatar'));
            $contactModified = true;
        }
        if (!$patch || null !== $this->getProperty($data, 'note')) {
            $contact->setNote($this->getProperty($data, 'note'));
            $contactModified = true;
        }
        if (!$patch || null !== $this->getProperty($data, 'medias')) {
            /** @var int[] $medias */
            $medias = $this->getProperty($data, 'medias', []);
            $this->setMedias($contact, $medias);
        }

        if (!$patch || $this->getProperty($data, 'title')) {
            $this->setTitleOnContact($contact, $this->getProperty($data, 'title'));
            $contactModified = true;
        }

        if (!$patch || $this->getProperty($data, 'formOfAddress')) {
            $formOfAddress = $this->getProperty($data, 'formOfAddress');

            if (\is_numeric($formOfAddress) || \is_string($formOfAddress)) {
                $contact->setFormOfAddress($formOfAddress);
                $contactModified = true;
            }

            if (!\is_null($formOfAddress) && \is_array($formOfAddress) && \array_key_exists('id', $formOfAddress)) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '1.x',
                    'Passing the "formOfAddress" as object is deprecated and will not be supported in Sulu 2.0'
                );
                $contact->setFormOfAddress($formOfAddress['id']);
                $contactModified = true;
            }
        }

        if (!$patch || $this->getProperty($data, 'salutation')) {
            $contact->setSalutation($this->getProperty($data, 'salutation'));
            $contactModified = true;
        }

        if (!$patch || $this->getProperty($data, 'birthday')) {
            $birthday = $this->getProperty($data, 'birthday');
            if (!empty($birthday)) {
                $birthday = new \DateTime($birthday);
            } else {
                $birthday = null;
            }
            $contact->setBirthday($birthday);
            $contactModified = true;
        }

        if (!$id) {
            $parentData = $this->getProperty($data, 'account');
            if (null != $parentData
                && null != $parentData['id']
                && 'null' != $parentData['id']
                && '' != $parentData['id']
            ) {
                /** @var AccountInterface $parent */
                $parent = $this->accountRepository->findAccountById($parentData['id']);
                if (!$parent) {
                    throw new EntityNotFoundException(
                        self::$accountContactEntityName,
                        $parentData['id']
                    );
                }

                // Set position on contact
                $positionId = $this->getProperty($data, 'position');
                $position = null;
                if ($positionId) {
                    $position = $this->getPosition($positionId);
                }

                // create new account-contact relation
                $this->createMainAccountContact(
                    $contact,
                    $parent,
                    $position
                );
            }
            // add urls, phones, emails, tags, bankAccounts, notes, addresses,..
            $this->addNewContactRelations($contact, $data);
            $this->processCategories($contact, $this->getProperty($data, 'categories', []));
        }

        $this->em->persist($contact);

        if ($isNewContact) {
            $this->domainEventCollector->collect(
                new ContactCreatedEvent($contact, $data)
            );
        } elseif ($contactModified) {
            $this->domainEventCollector->collect(
                new ContactModifiedEvent($contact, $data)
            );
        }

        if ($flush) {
            $this->em->flush();
        }

        return $contact;
    }

    /**
     * adds an address to the entity.
     *
     * @param ContactInterface $contact The entity to add the address to
     * @param Address $address The address to be added
     * @param bool $isMain Defines if the address is the main Address of the contact
     *
     * @return ContactAddress
     *
     * @throws \Exception
     */
    public function addAddress($contact, Address $address, $isMain)
    {
        if (!$contact || !$address) {
            throw new \Exception('Contact and Address cannot be null');
        }
        $contactAddress = new ContactAddress();
        $contactAddress->setContact($contact);
        $contactAddress->setAddress($address);
        if ($isMain) {
            $this->unsetMain($contact->getContactAddresses());
        }
        $contactAddress->setMain($isMain);
        $this->em->persist($contactAddress);

        $contact->addContactAddress($contactAddress);

        return $contactAddress;
    }

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations.
     *
     * @param ContactInterface $contact
     * @param ContactAddress $contactAddress
     *
     * @return void
     *
     * @throws \Exception
     */
    public function removeAddressRelation($contact, $contactAddress)
    {
        if (!$contact || !$contactAddress) {
            throw new \Exception('Contact and ContactAddress cannot be null');
        }

        // reload address to get all data (including relational data)
        /** @var Address $address */
        $address = $contactAddress->getAddress();
        $address = $this->em->getRepository(Address::class)->findById($address->getId());

        $isMain = $contactAddress->getMain();

        // remove relation
        $contact->removeContactAddress($contactAddress);
        $address->removeContactAddress($contactAddress);

        // if was main, set a new one
        if ($isMain) {
            $this->setMainForCollection($contact->getContactAddresses());
        }

        // delete address if it has no more relations
        if (!$address->hasRelations()) {
            $this->em->remove($address);
        }

        $this->em->remove($contactAddress);
    }

    /**
     * Returns a collection of relations to get addresses.
     *
     * @param ContactInterface $entity
     */
    public function getAddressRelations($entity)
    {
        return $entity->getContactAddresses();
    }

    /**
     * @param int $id
     * @param string $locale
     *
     * @throws EntityNotFoundException
     */
    public function getById($id, $locale)
    {
        $contact = $this->contactRepository->find($id);
        if (!$contact) {
            throw new EntityNotFoundException($this->contactRepository->getClassName(), $id);
        }

        return $this->getApiObject($contact, $locale);
    }

    /**
     * Returns an api entity for an doctrine entity.
     *
     * @param ContactInterface $contact
     * @param string $locale
     *
     * @return null|ContactApi
     */
    public function getContact($contact, $locale)
    {
        if ($contact) {
            return $this->getApiObject($contact, $locale);
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @throws EntityNotFoundException
     */
    public function setMainAccount(ContactInterface $contact, $data)
    {
        // set account relation
        if (isset($data['account'])
            && isset($data['account']['id'])
            && 'null' != $data['account']['id']
        ) {
            $accountId = $data['account']['id'];

            $account = $this->accountRepository->findAccountById($accountId);

            if (!$account) {
                throw new EntityNotFoundException($this->accountRepository->getClassName(), $accountId);
            }

            // get position
            $position = null;
            if (isset($data['position'])) {
                $position = $this->getPosition($data['position']);
            }

            // check if relation between account and contact already exists
            $mainAccountContact = $this->getMainAccountContact($contact);
            $accountContact = $this->getAccounContact($account, $contact);

            // remove previous main accountContact
            if ($mainAccountContact && $mainAccountContact !== $accountContact) {
                // if this contact is the main-Contact - set mainContact to null
                if ($mainAccountContact->getAccount()->getMainContact() === $contact) {
                    $mainAccountContact->getAccount()->setMainContact(null);
                }

                $this->domainEventCollector->collect(
                    new AccountContactRemovedEvent($mainAccountContact->getAccount(), $mainAccountContact->getContact())
                );

                $contact->removeAccountContact($mainAccountContact);
                $this->em->remove($mainAccountContact);
            }

            // if account-contact relation existed set params
            if ($accountContact) {
                $accountContact->setMain(true);
                $accountContact->setPosition($position);
            } else {
                // else create new one
                $accountContact = $this->createMainAccountContact($contact, $account, $position);
                $this->domainEventCollector->collect(new AccountContactAddedEvent($accountContact));
            }
        } else {
            // if a main account exists - remove it
            if ($accountContact = $this->getMainAccountContact($contact)) {
                // if this contact is the main-Contact - set mainContact to null
                if ($accountContact->getAccount()->getMainContact() === $contact) {
                    $accountContact->getAccount()->setMainContact(null);
                }

                $contact->removeAccountContact($accountContact);

                $this->domainEventCollector->collect(
                    new AccountContactRemovedEvent($accountContact->getAccount(), $accountContact->getContact())
                );

                $this->em->remove($accountContact);
            }
        }
    }

    /**
     * Sets a media with a given id as the avatar of a given contact.
     *
     * @param array $avatar with id property
     *
     * @throws EntityNotFoundException
     */
    private function setAvatar(ContactInterface $contact, $avatar)
    {
        $mediaEntity = null;
        if (\is_array($avatar) && $this->getProperty($avatar, 'id')) {
            $mediaId = $this->getProperty($avatar, 'id');
            $mediaEntity = $this->mediaRepository->findMediaById($mediaId);

            if (!$mediaEntity) {
                throw new EntityNotFoundException($this->mediaRepository->getClassName(), $mediaId);
            }
        }
        $contact->setAvatar($mediaEntity);
    }

    /**
     * Sets the medias of the given contact to the given medias.
     * Currently associated medias are replaced.
     *
     * @param int[] $mediaIds
     *
     * @throws EntityNotFoundException
     */
    private function setMedias(ContactInterface $contact, $mediaIds)
    {
        /** @var MediaInterface[] $foundMedias */
        $foundMedias = $this->mediaRepository->findById($mediaIds);
        /** @var int[] $foundMediaIds */
        $foundMediaIds = \array_map(
            function($mediaEntity) {
                return $mediaEntity->getId();
            },
            $foundMedias
        );

        if ($missingMediaIds = \array_diff($mediaIds, $foundMediaIds)) {
            throw new EntityNotFoundException($this->mediaRepository->getClassName(), \reset($missingMediaIds));
        }

        foreach ($contact->getMedias() as $media) {
            if (!\in_array($media->getId(), $foundMediaIds)) {
                $contact->removeMedia($media);

                $this->domainEventCollector->collect(
                    new ContactMediaRemovedEvent($contact, $media)
                );
            }
        }

        foreach ($foundMedias as $media) {
            if (!$contact->getMedias()->contains($media)) {
                $contact->addMedia($media);

                $this->domainEventCollector->collect(
                    new ContactMediaAddedEvent($contact, $media)
                );
            }
        }
    }

    /**
     * Takes a contact entity and a locale and returns the api object.
     *
     * @param ContactInterface $contact
     * @param string $locale
     *
     * @return ContactApi
     */
    protected function getApiObject($contact, $locale)
    {
        $apiObject = new ContactApi($contact, $locale);
        if ($contact->getAvatar()) {
            $apiAvatar = $this->mediaManager->getById($contact->getAvatar()->getId(), $locale);
            $apiObject->setAvatar($apiAvatar);
        }

        return $apiObject;
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
     * @param ContactInterface $contact
     * @param int $titleId
     */
    public function setTitleOnContact($contact, $titleId)
    {
        if ($titleId && \is_numeric($titleId)) {
            $title = $this->contactTitleRepository->find($titleId);
            if ($title) {
                $contact->setTitle($title);
            }
        } else {
            $contact->setTitle(null);
        }
    }

    /**
     * Get contact entity name.
     *
     * @return string
     */
    public function getContactEntityName()
    {
        return $this->contactRepository->getClassName();
    }

    /**
     * Get a position object.
     *
     * @param int $id The position id
     */
    public function getPosition($id)
    {
        return $this->em->getRepository(self::$positionEntityName)->find($id);
    }

    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = [])
    {
        $entities = $this->contactRepository->findByFilters($filters, $page, $pageSize, $limit, $locale, $options);

        return \array_map(
            function($contact) use ($locale) {
                return $this->getApiObject($contact, $locale);
            },
            $entities
        );
    }
}
