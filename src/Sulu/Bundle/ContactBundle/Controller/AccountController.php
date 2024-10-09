<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Contact\AccountFactoryInterface;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes accounts available through a REST API.
 */
class AccountController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * @deprecated Use the AccountInterface::RESOURCE_KEY constant instead
     */
    protected static $entityKey = 'accounts';

    protected static $positionEntityName = 'SuluContactBundle:Position';

    /**
     * @deprecated Use the ContactInterface::RESOURCE_KEY constant instead
     */
    protected static $contactEntityKey = 'contacts';

    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';

    protected static $addressEntityName = 'SuluContactBundle:Address';

    protected static $accountAddressEntityName = 'SuluContactBundle:AccountAddress';

    protected static $accountSerializationGroups = [
        'fullAccount',
        'partialContact',
        'partialMedia',
        'partialTag',
        'partialCategory',
    ];

    protected $bundlePrefix = 'contact.accounts.';

    protected $locale;

    // TODO: Move the field descriptors to a manager -
    // or better -> refactor the whole file!

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    protected $accountContactFieldDescriptors;

    protected $accountAddressesFieldDescriptors;

    /**
     * @param class-string $accountClass
     * @param class-string $contactClass
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private MediaManagerInterface $mediaManager,
        private AccountRepositoryInterface $accountRepository,
        private EntityManagerInterface $entityManager,
        private AccountManager $accountManager,
        private AccountFactoryInterface $accountFactory,
        private DomainEventCollectorInterface $domainEventCollector,
        private string $accountClass,
        private string $contactClass,
        private ?TrashManagerInterface $trashManager
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    /**
     * Lists all contacts of an account.
     * optional parameter 'flat' calls listAction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getContactsAction($id, Request $request)
    {
        if ('true' == $request->get('flat')) {
            /* @var AccountInterface $account */
            $account = $this->accountRepository->findById($id);

            $listBuilder = $this->listBuilderFactory->create(self::$accountContactEntityName);

            $fieldDescriptors = $this->getAccountContactFieldDescriptors();
            $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $listBuilder->addSelectField($fieldDescriptors['contactId']);
            $listBuilder->setIdField($fieldDescriptors['id']);
            $listBuilder->where($fieldDescriptors['account'], $id);
            $listBuilder->sort($fieldDescriptors['lastName'], $listBuilder::SORTORDER_ASC);

            $values = $listBuilder->execute();

            foreach ($values as &$value) {
                // Substitute id since we are interested in the contact id not the accountContact id.
                $value['id'] = $value['contactId'];
                unset($value['contactId']);

                if (null != $account->getMainContact() && $value['id'] === $account->getMainContact()->getId()) {
                    $value['isMainContact'] = true;
                } else {
                    $value['isMainContact'] = false;
                }
            }

            $list = new ListRepresentation(
                $values,
                'account_contacts',
                'sulu_contact.get_account_addresses',
                \array_merge(['id' => $id], $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $locale = $this->getUser()->getLocale();
            $contacts = $this->accountManager->findContactsByAccountId($id, $locale, false);
            $list = new CollectionRepresentation($contacts, ContactInterface::RESOURCE_KEY);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Lists all addresses of an account
     * optional parameter 'flat' calls listAction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAddressesAction($id, Request $request)
    {
        if ('true' == $request->get('flat')) {
            $listBuilder = $this->listBuilderFactory->create($this->getAccountEntityName());

            $this->restHelper->initializeListBuilder($listBuilder, $this->getAccountAddressesFieldDescriptors());

            $listBuilder->where($this->getFieldDescriptors()['id'], $id);

            $values = $listBuilder->execute();

            $list = new ListRepresentation(
                $values,
                'addresses',
                'sulu_contact.get_account_addresses',
                \array_merge(['id' => $id], $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $addresses = $this->entityManager->getRepository(self::$addressEntityName)->findByAccountId($id);
            $list = new CollectionRepresentation($addresses, 'addresses');
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * @param int $accountId
     * @param int $contactId
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function putContactsAction($accountId, $contactId, Request $request)
    {
        try {
            // Get account.
            $account = $this->accountRepository->findById($accountId);
            if (!$account) {
                throw new EntityNotFoundException('account', $accountId);
            }

            // Get contact.
            $contact = $this->entityManager
                ->getRepository($this->contactClass)
                ->find($contactId);
            if (!$contact) {
                throw new EntityNotFoundException('contact', $contactId);
            }

            // Check if relation already exists.
            $accountContact = $this->entityManager
                ->getRepository(self::$accountContactEntityName)
                ->findOneBy(['contact' => $contact, 'account' => $account]);
            if ($accountContact) {
                throw new \Exception('Relation already exists');
            }

            // Create relation.
            $accountContact = new AccountContactEntity();
            // If contact has no main relation - set as main.
            $accountContact->setMain($contact->getAccountContacts()->isEmpty());
            $accountContact->setAccount($account);
            $accountContact->setContact($contact);

            // Set position on contact.
            $positionId = $request->get('position');
            $position = null;

            if ($positionId) {
                $position = $this->entityManager
                    ->getRepository(static::$positionEntityName)
                    ->find($positionId);

                $accountContact->setPosition($position);
            }

            $this->entityManager->persist($accountContact);
            $this->domainEventCollector->collect(new AccountContactAddedEvent($accountContact));
            $this->entityManager->flush();

            $isMainContact = false;
            if ($account->getMainContact()) {
                $isMainContact = $account->getMainContact()->getId() === $contact->getId();
            }

            $contactArray = [
                'id' => $contact->getId(),
                'fullName' => $contact->getFullName(),
                'isMainContact' => $isMainContact,
            ];

            if ($position) {
                $contactArray['position'] = $position->getPosition();
            }

            $view = $this->view($contactArray, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deleted account contact.
     *
     * @param int $accountId
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function deleteContactsAction($accountId, $id)
    {
        try {
            // Check if relation exists.
            /** @var AccountContactEntity $accountContact */
            $accountContact = $this->entityManager
                ->getRepository(self::$accountContactEntityName)
                ->findByForeignIds($accountId, $id);

            if (!$accountContact) {
                throw new EntityNotFoundException('AccountContact', $accountId . $id);
            }
            $id = $accountContact->getId();

            $account = $accountContact->getAccount();

            // Remove main contact when relation with main was removed.
            if ($account->getMainContact() && \strval($account->getMainContact()->getId()) === $id) {
                $account->setMainContact(null);
            }

            $this->entityManager->remove($accountContact);
            $this->domainEventCollector->collect(
                new AccountContactRemovedEvent($accountContact->getAccount(), $accountContact->getContact())
            );
            $this->entityManager->flush();

            $view = $this->view($id, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Lists all accounts.
     * Optional parameter 'flat' calls listAction.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getUser()->getLocale();

        if ('true' == $request->get('flat')) {
            $fieldDescriptors = $this->getFieldDescriptors();
            $listBuilder = $this->generateFlatListBuilder();
            $listBuilder->addGroupBy($fieldDescriptors['id']);
            $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
            $this->applyRequestParameters($request, $listBuilder);

            $listResponse = $listBuilder->execute();
            $listResponse = $this->addLogos($listResponse, $locale);

            $list = new ListRepresentation(
                $listResponse,
                AccountInterface::RESOURCE_KEY,
                'sulu_contact.get_accounts',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $filter = $this->retrieveFilter($request);
            $accounts = $this->accountManager->findAll($locale, $filter);
            $list = new CollectionRepresentation($accounts, AccountInterface::RESOURCE_KEY);
            $view = $this->view($list, 200);
        }

        $context = new Context();
        $context->setGroups(['fullAccount', 'partialContact', 'Default']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Creates a listbuilder instance.
     *
     * @return DoctrineListBuilder
     */
    protected function generateFlatListBuilder()
    {
        $listBuilder = $this->listBuilderFactory->create($this->getAccountEntityName());

        return $listBuilder;
    }

    /**
     * Applies the filter parameter and hasNoparent parameter for listbuilder.
     *
     * @param DoctrineListBuilder $listBuilder
     */
    protected function applyRequestParameters(Request $request, $listBuilder)
    {
        if (\json_decode($request->get('hasNoParent', null))) {
            $listBuilder->where($this->getFieldDescriptorForNoParent(), null);
        }

        if (\json_decode($request->get('hasEmail', null))) {
            $listBuilder->whereNot($this->getFieldDescriptors()['mainEmail'], null);
        }
    }

    /**
     * Returns fielddescriptor used for checking if account has no parent
     * Will result in an error when added to the array of fielddescriptors
     * because its just for checking if parent exists or not and does not
     * point to a property of the parent.
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getFieldDescriptorForNoParent()
    {
        return new DoctrineFieldDescriptor(
            'parent',
            'parent',
            $this->getAccountEntityName(),
            'contact.accounts.company',
            [],
            FieldDescriptorInterface::VISIBILITY_NO,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
    }

    /**
     * Creates a new account.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        try {
            if (null === $name) {
                throw new RestException('There is no name for the account given');
            }

            $account = $this->doPost($request);
            $this->entityManager->persist($account);
            $this->domainEventCollector->collect(new AccountCreatedEvent($account, $request->request->all()));
            $this->entityManager->flush();

            $locale = $this->getUser()->getLocale();
            $acc = $this->accountManager->getAccount($account, $locale);
            $view = $this->view($acc, 200);

            $context = new Context();
            $context->setGroups(self::$accountSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Maps data from request to a new account.
     *
     * @return AccountInterface
     *
     * @throws EntityNotFoundException
     */
    protected function doPost(Request $request)
    {
        $account = $this->accountFactory->createEntity();
        $account->setName($request->get('name'));
        $account->setCorporation($request->get('corporation'));

        if (null !== $request->get('uid')) {
            $account->setUid($request->get('uid'));
        }

        if (null !== $request->get('note')) {
            $account->setNote($request->get('note'));
        }

        $logo = $request->get('logo', []);
        if ($logo && \array_key_exists('id', $logo)) {
            $this->accountManager->setLogo($account, $request->get('logo')['id']);
        }

        $this->setParent($request->get('parent'), $account);

        $this->accountManager->processCategories($account, $request->get('categories', []));

        $account->setCreator($this->getUser());
        $account->setChanger($this->getUser());

        // Add urls, phones, emails, tags, bankAccounts, notes, addresses,..
        $this->accountManager->addNewContactRelations($account, $request->request->all());

        return $account;
    }

    /**
     * Edits the existing contact with the given id.
     *
     * @param int $id The id of the contact to update
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        try {
            $account = $this->accountRepository->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {
                $this->doPut($account, $request, $this->entityManager);

                $this->domainEventCollector->collect(new AccountModifiedEvent($account, $request->request->all()));
                $this->entityManager->flush();

                // get api entity
                $locale = $this->getUser()->getLocale();
                $acc = $this->accountManager->getAccount($account, $locale);

                $context = new Context();
                $context->setGroups(self::$accountSerializationGroups);

                $view = $this->view($acc, 200);
                $view->setContext($context);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * processes given entity for put.
     *
     * @throws EntityNotFoundException
     * @throws RestException
     */
    protected function doPut(AccountInterface $account, Request $request, ObjectManager $entityManager)
    {
        $account->setName($request->get('name'));
        $account->setCorporation($request->get('corporation'));
        $accountManager = $this->accountManager;

        $account->setUid($request->get('uid'));
        $account->setNote($request->get('note'));

        $logo = $request->get('logo', []);
        if ($logo && \array_key_exists('id', $logo)) {
            $accountManager->setLogo($account, $request->get('logo')['id']);
        }

        $this->setParent($request->get('parent'), $account);

        $mainContact = null;
        if (null !== ($mainContactRequest = $request->get('mainContact'))) {
            $mainContact = $entityManager->getRepository(
                $this->contactClass
            )->find($mainContactRequest['id']);
        }

        $account->setMainContact($mainContact);

        $user = $this->getUser();
        $account->setChanger($user);

        $contactDetailsData = $request->get('contactDetails', []);

        // Process details
        if (!($accountManager->processUrls($account, $contactDetailsData['websites'] ?? [])
            && $accountManager->processEmails($account, $contactDetailsData['emails'] ?? [])
            && $accountManager->processFaxes($account, $contactDetailsData['faxes'] ?? [])
            && $accountManager->processSocialMediaProfiles($account, $contactDetailsData['socialMedia'] ?? [])
            && $accountManager->processPhones($account, $contactDetailsData['phones'] ?? [])
            && $accountManager->processAddresses($account, $request->get('addresses', []))
            && $accountManager->processTags($account, $request->get('tags', []))
            && $accountManager->processNotes($account, $request->get('notes', []))
            && $accountManager->processCategories($account, $request->get('categories', []))
            && $accountManager->processBankAccounts($account, $request->get('bankAccounts', [])))
        ) {
            throw new RestException('Updating dependencies is not possible', 0);
        }
    }

    /**
     * Set parent to account.
     *
     * @param array $parentData
     *
     * @throws EntityNotFoundException
     */
    private function setParent($parentData, AccountInterface $account): void
    {
        if (null != $parentData && isset($parentData['id']) && 'null' !== $parentData['id'] && '' !== $parentData['id']) {
            $parent = $this->accountRepository->findAccountById($parentData['id']);
            if (!$parent) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $parentData['id']);
            }
            $account->setParent($parent);
        } else {
            $account->setParent(null);
        }
    }

    /**
     * Partial update of account infos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function patchAction($id, Request $request)
    {
        try {
            $account = $this->accountRepository->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {
                $this->doPatch($account, $request, $this->entityManager);
                $this->entityManager->flush();

                // get api entity
                $locale = $this->getUser()->getLocale();
                $acc = $this->accountManager->getAccount($account, $locale);

                $context = new Context();
                $context->setGroups(self::$accountSerializationGroups);

                $view = $this->view($acc, 200);
                $view->setContext($context);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Process geiven entity for patch.
     */
    protected function doPatch(AccountInterface $account, Request $request, ObjectManager $entityManager)
    {
        $accountManager = $this->accountManager;

        $accountModified = false;
        if (null !== $request->get('uid')) {
            $account->setUid($request->get('uid'));
            $accountModified = true;
        }
        if (null !== $request->get('registerNumber')) {
            $account->setRegisterNumber($request->get('registerNumber'));
            $accountModified = true;
        }
        if (null !== $request->get('number')) {
            $account->setNumber($request->get('number'));
            $accountModified = true;
        }
        if (null !== $request->get('placeOfJurisdiction')) {
            $account->setPlaceOfJurisdiction($request->get('placeOfJurisdiction'));
            $accountModified = true;
        }
        if (\array_key_exists('id', $request->get('logo', []))) {
            $accountManager->setLogo($account, $request->get('logo')['id']);
            $accountModified = true;
        }
        if (null !== $request->get('medias')) {
            $accountManager->setMedias($account, $request->get('medias'));
        }

        $mainContact = null;
        if (null !== ($mainContactRequest = $request->get('mainContact'))) {
            $mainContact = $entityManager->getRepository($this->contactClass)->find($mainContactRequest['id']);
            $accountModified = true;
        }

        if (null !== $request->get('bankAccounts')) {
            $accountManager->processBankAccounts($account, $request->get('bankAccounts', []));
            $accountModified = true;
        }

        $account->setMainContact($mainContact);

        if ($accountModified) {
            $this->domainEventCollector->collect(new AccountModifiedEvent($account, $request->request->all()));
        }
    }

    /**
     * Delete an account with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id, Request $request)
    {
        $children = $this->accountRepository->findChildAccounts($id);
        if (\count($children) > 0) {
            $data = [
                'id' => $id,
                'items' => [],
            ];

            foreach ($children as $child) {
                $data['items'][] = ['name' => $child->getName()];
            }

            return $this->handleView($this->view($data, 409));
        }

        $delete = function($id) use ($request) {
            $account = $this->accountRepository->findAccountByIdAndDelete($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            }

            if ($this->trashManager) {
                $this->trashManager->store(AccountInterface::RESOURCE_KEY, $account);
            }

            $addresses = $account->getAddresses();
            /** @var AddressEntity $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $this->entityManager->remove($address);
                }
            }

            // Remove related contacts if removeContacts is true.
            if (null !== $request->get('removeContacts')
                && 'true' == $request->get('removeContacts')
            ) {
                foreach ($account->getAccountContacts() as $accountContact) {
                    $this->entityManager->remove($accountContact->getContact());
                }
            }

            $this->entityManager->remove($account);
            $this->domainEventCollector->collect(new AccountRemovedEvent($account->getId(), $account->getName()));
            $this->entityManager->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Returns delete info for multiple ids.
     *
     * @return Response
     *
     * TODO remove?
     */
    public function multipledeleteinfoAction(Request $request)
    {
        $ids = $request->get('ids');

        $response = [];
        $numContacts = 0;
        $numChildren = 0;

        foreach ($ids as $id) {
            $account = $this->accountRepository->countDistinctAccountChildrenAndContacts($id);

            // Get number of subaccounts.
            $numChildren += $account['numChildren'];

            // FIXME: Distinct contacts: (currently the same contacts could be counted multiple times).
            // Get full number of contacts.
            $numContacts += $account['numContacts'];
        }

        $response['numContacts'] = $numContacts;
        $response['numChildren'] = $numChildren;

        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Returns information about data which will be also deleted:
     * 3 contacts, total number of contacts, and if deleting is allowed (as 0 or 1).
     *
     * @param int $id
     *
     * @return Response
     */
    public function getDeleteinfoAction($id)
    {
        $response = [];
        $response['contacts'] = [];

        $account = $this->accountRepository->findChildrenAndContacts($id);

        if (null !== $account) {
            // Return a maximum of 3 accounts.
            $slicedContacts = [];
            $accountContacts = $account->getAccountContacts();
            $numContacts = 0;
            if (null !== $accountContacts) {
                foreach ($accountContacts as $accountContact) {
                    /* @var AccountContactEntity $accountContact */
                    $contactId = $accountContact->getContact()->getId();
                    if (!\array_key_exists($contactId, $slicedContacts)) {
                        if ($numContacts++ < 3) {
                            $slicedContacts[$contactId] = $accountContact->getContact();
                        }
                    }
                }
            }

            foreach ($slicedContacts as $contact) {
                /* @var ContactEntity $contact */
                $response['contacts'][] = [
                    'id' => $contact->getId(),
                    'firstName' => $contact->getFirstName(),
                    'middleName' => $contact->getMiddleName(),
                    'lastName' => $contact->getLastName(),
                ];
            }

            // return number of contact
            $response['numContacts'] = $numContacts;

            // get number of sub companies
            $response['numChildren'] = $account->getChildren()->count();

            if ($response['numChildren'] > 0) {
                // if account has a subcompany do not allow to delete
                $slicedChildren = $account->getChildren()->slice(0, 3);

                /* @var AccountInterface $sc */
                foreach ($slicedChildren as $sc) {
                    $child = [];
                    $child['id'] = $sc->getId();
                    $child['name'] = $sc->getName();

                    $response['children'][] = $child;
                }
            }

            $view = $this->view($response, 200);
        } else {
            $view = $this->view(null, 404);
        }

        return $this->handleView($view);
    }

    /**
     * Shows a single account with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        $includes = \explode(',', $request->get('include'));
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function($id) use ($includes, $locale) {
                    return $this->accountManager->getByIdAndInclude($id, $locale, $includes);
                }
            );

            $context = new Context();
            $context->setGroups(self::$accountSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    public function getSecurityContext()
    {
        return 'sulu.contact.organizations';
    }

    protected function getFieldDescriptors()
    {
        if (null === $this->fieldDescriptors) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    /**
     * @return null|array
     */
    protected function getAccountContactFieldDescriptors()
    {
        if (null === $this->accountContactFieldDescriptors) {
            $this->initAccountContactFieldDescriptors();
        }

        return $this->accountContactFieldDescriptors;
    }

    /**
     * @return null|array
     */
    protected function getAccountAddressesFieldDescriptors()
    {
        if (null === $this->accountAddressesFieldDescriptors) {
            $this->initAccountAddressesFieldDescriptors();
        }

        return $this->accountAddressesFieldDescriptors;
    }

    /**
     * Inits the account contact descriptors.
     */
    protected function initAccountContactFieldDescriptors()
    {
        $this->accountContactFieldDescriptors = [];
        $contactJoin = [
            $this->contactClass => new DoctrineJoinDescriptor(
                $this->contactClass,
                self::$accountContactEntityName . '.contact'
            ),
        ];

        $this->accountContactFieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$accountContactEntityName,
            'contact.contacts.main-contact',
            [],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            false
        );

        $this->accountContactFieldDescriptors['contactId'] = new DoctrineFieldDescriptor(
            'id',
            'contactId',
            $this->contactClass,
            'contact.contacts.main-contact',
            [],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            false
        );

        $this->accountContactFieldDescriptors['account'] = new DoctrineFieldDescriptor(
            'id',
            'accountId',
            $this->accountClass,
            '',
            [
                $this->accountClass => new DoctrineJoinDescriptor(
                    $this->accountClass,
                    self::$accountContactEntityName . '.account'
                ),
            ]
        );

        $this->accountContactFieldDescriptors['firstName'] = new DoctrineFieldDescriptor(
            'firstName',
            'firstName',
            $this->contactClass,
            'contact.contacts.firstname',
            $contactJoin,
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            false
        );

        $this->accountContactFieldDescriptors['lastName'] = new DoctrineFieldDescriptor(
            'lastName',
            'lastName',
            $this->contactClass,
            'contact.contacts.lastName',
            $contactJoin,
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            false
        );

        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor(
                    'firstName',
                    'mainContact',
                    $this->contactClass,
                    'contact.contacts.main-contact',
                    $contactJoin
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'mainContact',
                    $this->contactClass,
                    'contact.contacts.main-contact',
                    $contactJoin
                ),
            ],
            'fullName',
            'public.name',
            ' ',
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            '',
            false
        );

        $this->accountContactFieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            [
                self::$positionEntityName => new DoctrineJoinDescriptor(
                    self::$positionEntityName,
                    self::$accountContactEntityName . '.position'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            false
        );

        // FIXME use field descriptor with expression when implemented
        $this->accountContactFieldDescriptors['isMainContact'] = new DoctrineFieldDescriptor(
            'main',
            'isMainContact',
            self::$accountContactEntityName,
            'contact.contacts.main-contact',
            [],
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            'radio',
            false
        );
    }

    /**
     * Inits the account contact descriptors.
     */
    protected function initAccountAddressesFieldDescriptors()
    {
        $this->accountAddressesFieldDescriptors = [];

        $addressJoin = [
            self::$accountAddressEntityName => new DoctrineJoinDescriptor(
                self::$accountAddressEntityName,
                $this->getAccountEntityName() . '.accountAddresses'
            ),
            self::$addressEntityName => new DoctrineJoinDescriptor(
                self::$addressEntityName,
                self::$accountAddressEntityName . '.address'
            ),
        ];

        $this->accountAddressesFieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$addressEntityName,
            'contact.contacts.address',
            $addressJoin,
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            false
        );

        $this->accountAddressesFieldDescriptors['address'] = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineConcatenationFieldDescriptor(
                    [
                        new DoctrineFieldDescriptor(
                            'street',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                        new DoctrineFieldDescriptor(
                            'number',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                        new DoctrineFieldDescriptor(
                            'addition',
                            'address',
                            self::$addressEntityName,
                            '',
                            $addressJoin
                        ),
                    ],
                    'street',
                    ' '
                ),
                new DoctrineFieldDescriptor(
                    'zip',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'city',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'state',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'countryCode',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
                new DoctrineFieldDescriptor(
                    'postboxNumber',
                    'address',
                    self::$addressEntityName,
                    '',
                    $addressJoin
                ),
            ],
            'address',
            'public.address',
            ', ',
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NO
        );
    }

    /**
     * Initializes the field descriptors.
     */
    protected function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('accounts');
    }

    /**
     * @return class-string
     */
    protected function getAccountEntityName()
    {
        return $this->accountClass;
    }

    /**
     * Takes an array of accounts and resets the logo-property containing the media id with
     * the actual urls to the logo thumbnails.
     *
     * @param array $accounts
     * @param string $locale
     *
     * @return array
     */
    private function addLogos($accounts, $locale)
    {
        $ids = \array_filter(\array_column($accounts, 'logo'));
        $logos = $this->mediaManager->getFormatUrls($ids, $locale);
        foreach ($accounts as $key => $account) {
            if (\array_key_exists('logo', $account) && $account['logo'] && \array_key_exists($account['logo'], $logos)) {
                $accounts[$key]['logo'] = $logos[$account['logo']];
            }
        }

        return $accounts;
    }

    /**
     * Retrieves the ids from the request.
     *
     * @return array
     */
    private function retrieveFilter(Request $request)
    {
        $filter = [];
        $ids = $request->get('ids');

        if ($ids) {
            if (\is_string($ids)) {
                $ids = \explode(',', $ids);
            }

            $filter['id'] = $ids;
        }

        return $filter;
    }
}
