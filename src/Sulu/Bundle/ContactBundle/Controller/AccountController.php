<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes accounts available through a REST API.
 */
class AccountController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'accounts';

    protected static $positionEntityName = 'SuluContactBundle:Position';

    protected static $contactEntityKey = 'contacts';

    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';

    protected static $addressEntityName = 'SuluContactBundle:Address';

    protected static $accountAddressEntityName = 'SuluContactBundle:AccountAddress';

    protected static $countryEntityName = 'SuluContactBundle:Country';

    protected static $accountSerializationGroups = [
        'fullAccount',
        'partialContact',
        'partialMedia',
        'partialTag',
        'partialCategory',
    ];

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.accounts.';

    /**
     * @var AccountManager
     */
    protected $accountManager;

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
     * Lists all contacts of an account.
     * optional parameter 'flat' calls listAction.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function getContactsAction($id, Request $request)
    {
        if ('true' == $request->get('flat')) {
            /* @var AccountInterface $account */
            $account = $this->getRepository()->findById($id);

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$accountContactEntityName);

            $fieldDescriptors = $this->getAccountContactFieldDescriptors();
            $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

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
                'contacts',
                'get_account_contacts',
                array_merge(['id' => $id], $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $contactManager = $this->getAccountManager();
            $locale = $this->getUser()->getLocale();
            $contacts = $contactManager->findContactsByAccountId($id, $locale, false);
            $list = new CollectionRepresentation($contacts, self::$contactEntityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Lists all addresses of an account
     * optional parameter 'flat' calls listAction.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function getAddressesAction($id, Request $request)
    {
        if ('true' == $request->get('flat')) {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create($this->getAccountEntityName());

            $restHelper->initializeListBuilder($listBuilder, $this->getAccountAddressesFieldDescriptors());

            $listBuilder->where($this->getFieldDescriptors()['id'], $id);

            $values = $listBuilder->execute();

            $list = new ListRepresentation(
                $values,
                'addresses',
                'get_account_addresses',
                array_merge(['id' => $id], $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $addresses = $this->getDoctrine()->getRepository(self::$addressEntityName)->findByAccountId($id);
            $list = new CollectionRepresentation($addresses, 'addresses');
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * @param int $accountId
     * @param int j$contactId
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function putContactsAction($accountId, $contactId, Request $request)
    {
        try {
            // Get account.
            $account = $this->getRepository()->findById($accountId);
            if (!$account) {
                throw new EntityNotFoundException('account', $accountId);
            }

            // Get contact.
            $contact = $this->getDoctrine()
                ->getRepository($this->container->getParameter('sulu.model.contact.class'))
                ->find($contactId);
            if (!$contact) {
                throw new EntityNotFoundException('contact', $contactId);
            }

            // Check if relation already exists.
            $accountContact = $this->getDoctrine()
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
            $position = $this->getAccountManager()->getPosition($request->get('position', null));
            $accountContact->setPosition($position);
            $contact->setCurrentPosition($position);

            $em = $this->getDoctrine()->getManager();
            $em->persist($accountContact);
            $em->flush();

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
     * @param int $contactId
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function deleteContactsAction($accountId, $contactId)
    {
        try {
            // Check if relation exists.
            /** @var AccountContactEntity $accountContact */
            $accountContact = $this->getDoctrine()
                ->getRepository(self::$accountContactEntityName)
                ->findByForeignIds($accountId, $contactId);

            if (!$accountContact) {
                throw new EntityNotFoundException('AccountContact', $accountId . $contactId);
            }
            $id = $accountContact->getId();

            $account = $accountContact->getAccount();

            // Remove main contact when relation with main was removed.
            if ($account->getMainContact() && strval($account->getMainContact()->getId()) === $contactId) {
                $account->setMainContact(null);
            }

            // Remove accountContact.
            $em = $this->getDoctrine()->getManager();
            $em->remove($accountContact);
            $em->flush();

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
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $numberIdsFilter = 0;
        $requestLimit = $request->get('limit');
        $locale = $this->getUser()->getLocale();
        $filter = $this->retrieveFilter($request, $numberIdsFilter);

        if ('true' == $request->get('flat')) {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            $fieldDescriptors = $this->getFieldDescriptors();
            $listBuilder = $this->generateFlatListBuilder();
            $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
            $this->applyRequestParameters($request, $filter, $listBuilder);

            // If no limit is set in request and limit is set by ids
            if (!$requestLimit && $numberIdsFilter > 0 && $numberIdsFilter > $listBuilder->getLimit()) {
                $listBuilder->limit($numberIdsFilter);
            }

            $listResponse = $listBuilder->execute();
            $listResponse = $this->addLogos($listResponse, $locale);

            $list = new ListRepresentation(
                $listResponse,
                self::$entityKey,
                'get_accounts',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $accountManager = $this->getAccountManager();
            $accounts = $accountManager->findAll($locale, $filter);
            $list = new CollectionRepresentation($accounts, self::$entityKey);
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
        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $listBuilder = $factory->create($this->getAccountEntityName());

        return $listBuilder;
    }

    /**
     * Applies the filter parameter and hasNoparent parameter for listbuilder.
     *
     * @param Request $request
     * @param array $filter
     * @param DoctrineListBuilder $listBuilder
     */
    protected function applyRequestParameters(Request $request, $filter, $listBuilder)
    {
        if (json_decode($request->get('hasNoParent', null))) {
            $listBuilder->where($this->getFieldDescriptorForNoParent(), null);
        }

        if (json_decode($request->get('hasEmail', null))) {
            $listBuilder->whereNot($this->getFieldDescriptors()['mainEmail'], null);
        }

        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $listBuilder->in($this->getFieldDescriptors()[$key], $value);
            } else {
                $listBuilder->where($this->getFieldDescriptors()[$key], $value);
            }
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
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        try {
            if (null == $name) {
                throw new RestException('There is no name for the account given');
            }

            $em = $this->getDoctrine()->getManager();
            $account = $this->doPost($request);
            $em->persist($account);
            $em->flush();

            $accountManager = $this->getAccountManager();
            $locale = $this->getUser()->getLocale();
            $acc = $accountManager->getAccount($account, $locale);
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
     * @param Request $request
     *
     * @throws EntityNotFoundException
     *
     * @return AccountInterface
     */
    protected function doPost(Request $request)
    {
        $accountManager = $this->getAccountManager();
        $account = $this->get('sulu_contact.account_factory')->createEntity();
        $account->setName($request->get('name'));
        $account->setCorporation($request->get('corporation'));

        if (null !== $request->get('uid')) {
            $account->setUid($request->get('uid'));
        }

        if (null !== $request->get('note')) {
            $account->setNote($request->get('note'));
        }

        if (array_key_exists('id', $request->get('logo', []))) {
            $accountManager->setLogo($account, $request->get('logo')['id']);
        }

        $this->setParent($request->get('parent'), $account);

        $accountManager->processCategories($account, $request->get('categories', []));

        $account->setCreator($this->getUser());
        $account->setChanger($this->getUser());

        // Add urls, phones, emails, tags, bankAccounts, notes, addresses,..
        $accountManager->addNewContactRelations($account, $request->request->all());

        return $account;
    }

    /**
     * Edits the existing contact with the given id.
     *
     * @param int $id The id of the contact to update
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        try {
            $account = $this->getRepository()->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $this->doPut($account, $request);

                $em->flush();

                // get api entity
                $accountManager = $this->getAccountManager();
                $locale = $this->getUser()->getLocale();
                $acc = $accountManager->getAccount($account, $locale);

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
     * @param AccountInterface $account
     * @param Request $request
     *
     * @throws EntityNotFoundException
     * @throws RestException
     */
    protected function doPut(AccountInterface $account, Request $request)
    {
        $account->setName($request->get('name'));
        $account->setCorporation($request->get('corporation'));
        $accountManager = $this->getAccountManager();

        if (null !== $request->get('uid')) {
            $account->setUid($request->get('uid'));
        }

        if (null !== $request->get('note')) {
            $account->setNote($request->get('note'));
        }

        if (array_key_exists('id', $request->get('logo', []))) {
            $accountManager->setLogo($account, $request->get('logo')['id']);
        }

        $this->setParent($request->get('parent'), $account);

        $user = $this->getUser();
        $account->setChanger($user);

        // Process details
        if (!($accountManager->processUrls($account, $request->get('urls', []))
            && $accountManager->processEmails($account, $request->get('emails', []))
            && $accountManager->processFaxes($account, $request->get('faxes', []))
            && $accountManager->processSocialMediaProfiles($account, $request->get('socialMediaProfiles', []))
            && $accountManager->processPhones($account, $request->get('phones', []))
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
     * @param AccountInterface $account
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function setParent($parentData, AccountInterface $account)
    {
        if (null != $parentData && isset($parentData['id']) && 'null' != $parentData['id'] && '' != $parentData['id']) {
            $parent = $this->getRepository()->findAccountById($parentData['id']);
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
     * @param $id
     * @param Request $request
     *
     * @return Response
     */
    public function patchAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $account = $this->getRepository()->findAccountById($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            } else {
                $this->doPatch($account, $request, $em);

                $em->flush();

                // get api entity
                $accountManager = $this->getAccountManager();
                $locale = $this->getUser()->getLocale();
                $acc = $accountManager->getAccount($account, $locale);

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
     *
     * @param AccountInterface $account
     * @param Request $request
     * @param ObjectManager $entityManager
     */
    protected function doPatch(AccountInterface $account, Request $request, ObjectManager $entityManager)
    {
        $accountManager = $this->getAccountManager();
        if (null !== $request->get('uid')) {
            $account->setUid($request->get('uid'));
        }
        if (null !== $request->get('registerNumber')) {
            $account->setRegisterNumber($request->get('registerNumber'));
        }
        if (null !== $request->get('number')) {
            $account->setNumber($request->get('number'));
        }
        if (null !== $request->get('placeOfJurisdiction')) {
            $account->setPlaceOfJurisdiction($request->get('placeOfJurisdiction'));
        }
        if (array_key_exists('id', $request->get('logo', []))) {
            $accountManager->setLogo($account, $request->get('logo')['id']);
        }
        if (null !== $request->get('medias')) {
            $accountManager->setMedias($account, $request->get('medias'));
        }

        // Check if mainContact is set
        if (null !== ($mainContactRequest = $request->get('mainContact'))) {
            $mainContact = $entityManager->getRepository(
                $this->container->getParameter('sulu.model.contact.class')
            )->find($mainContactRequest['id']);
            if ($mainContact) {
                $account->setMainContact($mainContact);
            }
        }

        // Process details
        if (null !== $request->get('bankAccounts')) {
            $accountManager->processBankAccounts($account, $request->get('bankAccounts', []));
        }
    }

    /**
     * Delete an account with the given id.
     *
     * @param $id
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($id, Request $request)
    {
        $delete = function ($id) use ($request) {
            $account = $this->getRepository()->findAccountByIdAndDelete($id);

            if (!$account) {
                throw new EntityNotFoundException($this->getAccountEntityName(), $id);
            }

            $em = $this->getDoctrine()->getManager();

            $addresses = $account->getAddresses();
            /** @var AddressEntity $address */
            foreach ($addresses as $address) {
                if (!$address->hasRelations()) {
                    $em->remove($address);
                }
            }

            // Remove related contacts if removeContacts is true.
            if (null !== $request->get('removeContacts') &&
                'true' == $request->get('removeContacts')
            ) {
                foreach ($account->getAccountContacts() as $accountContact) {
                    $em->remove($accountContact->getContact());
                }
            }

            $em->remove($account);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Returns delete info for multiple ids.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function multipledeleteinfoAction(Request $request)
    {
        $ids = $request->get('ids');

        $response = [];
        $numContacts = 0;
        $numChildren = 0;

        foreach ($ids as $id) {
            $account = $this->getRepository()->countDistinctAccountChildrenAndContacts($id);

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
     * @param $id
     *
     * @return Response
     */
    public function getDeleteinfoAction($id)
    {
        $response = [];
        $response['contacts'] = [];

        $account = $this->getRepository()->findChildrenAndContacts($id);

        if (null != $account) {
            // Return a maximum of 3 accounts.
            $slicedContacts = [];
            $accountContacts = $account->getAccountContacts();
            $numContacts = 0;
            if (null !== $accountContacts) {
                foreach ($accountContacts as $accountContact) {
                    /* @var AccountContactEntity $accountContact */
                    $contactId = $accountContact->getContact()->getId();
                    if (!array_key_exists($contactId, $slicedContacts)) {
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
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        $includes = explode(',', $request->get('include'));
        $accountManager = $this->getAccountManager();
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function ($id) use ($includes, $accountManager, $locale) {
                    return $accountManager->getByIdAndInclude($id, $locale, $includes);
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

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.contact.organizations';
    }

    /**
     * @return AbstractContactManager
     */
    protected function getAccountManager()
    {
        return $this->get('sulu_contact.account_manager');
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
            $this->container->getParameter('sulu.model.contact.class') => new DoctrineJoinDescriptor(
                $this->container->getParameter('sulu.model.contact.class'),
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
            '',
            '',
            false
        );

        $this->accountContactFieldDescriptors['contactId'] = new DoctrineFieldDescriptor(
            'id',
            'contactId',
            $this->container->getParameter('sulu.model.contact.class'),
            'contact.contacts.main-contact',
            [],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            '',
            '',
            false
        );

        $this->accountContactFieldDescriptors['account'] = new DoctrineFieldDescriptor(
            'id',
            'accountId',
            $this->container->getParameter('sulu.model.account.class'),
            '',
            [
                $this->container->getParameter('sulu.model.account.class') => new DoctrineJoinDescriptor(
                    $this->container->getParameter('sulu.model.account.class'),
                    self::$accountContactEntityName . '.account'
                ),
            ]
        );

        $this->accountContactFieldDescriptors['firstName'] = new DoctrineFieldDescriptor(
            'firstName',
            'firstName',
            $this->container->getParameter('sulu.model.contact.class'),
            'contact.contacts.firstname',
            $contactJoin,
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            '',
            '',
            false
        );

        $this->accountContactFieldDescriptors['lastName'] = new DoctrineFieldDescriptor(
            'lastName',
            'lastName',
            $this->container->getParameter('sulu.model.contact.class'),
            'contact.contacts.lastName',
            $contactJoin,
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            '',
            '',
            false
        );

        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor(
                    'firstName',
                    'mainContact',
                    $this->container->getParameter('sulu.model.contact.class'),
                    'contact.contacts.main-contact',
                    $contactJoin
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'mainContact',
                    $this->container->getParameter('sulu.model.contact.class'),
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
            '',
            '160px',
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
            '',
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
            '',
            '',
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
        $countryJoin = [
            self::$countryEntityName => new DoctrineJoinDescriptor(
                self::$countryEntityName,
                self::$addressEntityName . '.country'
            ),
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
            '',
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
                    'name',
                    'address',
                    self::$countryEntityName,
                    '',
                    $countryJoin
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
            FieldDescriptorInterface::SEARCHABILITY_NO,
            '',
            '',
            '300px'
        );
    }

    /**
     * Initializes the field descriptors.
     */
    protected function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->get(
            'sulu_core.list_builder.field_descriptor_factory'
        )->getFieldDescriptorForClass($this->getAccountEntityName());
    }

    /**
     * @return string
     */
    protected function getAccountEntityName()
    {
        return $this->container->getParameter('sulu.model.account.class');
    }

    /**
     * @return RestHelperInterface
     */
    protected function getRestHelper()
    {
        return $this->get('sulu_core.doctrine_rest_helper');
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
        $ids = array_filter(array_column($accounts, 'logo'));
        $logos = $this->get('sulu_media.media_manager')->getFormatUrls($ids, $locale);
        foreach ($accounts as $key => $account) {
            if (array_key_exists('logo', $account) && $account['logo'] && array_key_exists($account['logo'], $logos)) {
                $accounts[$key]['logo'] = $logos[$account['logo']];
            }
        }

        return $accounts;
    }

    /**
     * Retrieves the ids from the request.
     *
     * @param Request $request
     * @param int &$count
     *
     * @return array
     */
    private function retrieveFilter(Request $request, &$count)
    {
        $filter = [];
        $ids = $request->get('ids');
        $count = 0;

        if ($ids) {
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }

            $count = count($ids);
            $filter['id'] = $ids;
        }

        return $filter;
    }

    /**
     * @return AccountRepositoryInterface
     */
    private function getRepository()
    {
        return $this->get('sulu.repository.account');
    }
}
