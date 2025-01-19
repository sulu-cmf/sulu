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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\ContactBundle\Util\IndexComparatorInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
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
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes contacts available through a REST API.
 */
class ContactController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $accountContactEntityName = \Sulu\Bundle\ContactBundle\Entity\AccountContact::class;

    protected static $positionEntityName = \Sulu\Bundle\ContactBundle\Entity\Position::class;

    // serialization groups for contact
    protected static $contactSerializationGroups = [
        'fullContact',
        'partialAccount',
        'partialTag',
        'partialMedia',
        'partialCategory',
    ];

    /**
     * @var string
     */
    protected $basePath = 'admin/api/contacts';

    protected $bundlePrefix = 'contact.contacts.';

    // TODO: move the field descriptors to a manager

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    protected $accountContactFieldDescriptors;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private RestHelperInterface $restHelper,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private ContactManagerInterface $contactManager,
        private ContactRepositoryInterface $contactRepository,
        private MediaManagerInterface $mediaManager,
        private UserRepositoryInterface $userRepository,
        private IndexComparatorInterface $indexComparator,
        private string $contactClass,
        private string $suluSecuritySystem
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    protected function getFieldDescriptors()
    {
        if (null === $this->fieldDescriptors) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    protected function getAccountContactFieldDescriptors()
    {
        if (null === $this->accountContactFieldDescriptors) {
            $this->initFieldDescriptors();
        }

        return $this->accountContactFieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('contacts');

        // field descriptors for the account contact list
        $this->accountContactFieldDescriptors = [];
        $this->accountContactFieldDescriptors['id'] = $this->fieldDescriptors['id'];
        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor(
                    'firstName',
                    'firstName',
                    $this->contactClass
                ),
                new DoctrineFieldDescriptor(
                    'lastName',
                    'lastName',
                    $this->contactClass
                ),
            ],
            'fullName',
            'public.name',
            ' ',
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_YES,
            'string',
            false
        );
        $this->accountContactFieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            [
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    $this->contactClass . '.accountContacts'
                ),
                self::$positionEntityName => new DoctrineJoinDescriptor(
                    self::$positionEntityName,
                    self::$accountContactEntityName . '.position'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'string',
            false
        );

        // FIXME use field descriptor with expression when implemented
        $this->accountContactFieldDescriptors['isMainContact'] = new DoctrineFieldDescriptor(
            'main',
            'isMainContact',
            self::$accountContactEntityName,
            'contact.contacts.main-contact',
            [
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    $this->contactClass . '.accountContacts'
                ),
            ],
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NO,
            'radio',
            false
        );
    }

    /**
     * lists all contacts
     * optional parameter 'flat' calls listAction.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $serializationGroups = [];
        $locale = $this->getLocale($request);
        $excludedAccountId = $request->query->get('excludedAccountId');

        if ('true' == $request->get('flat')) {
            $list = $this->getList($request, $locale);
        } else {
            if (true == $request->get('bySystem')) {
                $contacts = $this->getContactsByUserSystem();
                $serializationGroups[] = 'select';
            } elseif ($excludedAccountId) {
                $contacts = $this->contactRepository->findByExcludedAccountId($excludedAccountId, $request->get('search'));
                $serializationGroups[] = 'select';
            } else {
                $contacts = $this->contactRepository->findAll();
                $serializationGroups = \array_merge(
                    $serializationGroups,
                    static::$contactSerializationGroups
                );
            }

            // convert to api-contacts
            $apiContacts = [];
            foreach ($contacts as $contact) {
                $apiContacts[] = $this->contactManager->getContact($contact, $locale);
            }

            $list = new CollectionRepresentation($apiContacts, ContactInterface::RESOURCE_KEY);
        }

        $view = $this->view($list, 200);

        // set serialization groups
        if (\count($serializationGroups) > 0) {
            $context = new Context();
            $context->setGroups($serializationGroups);
            $view->setContext($context);
        }

        return $this->handleView($view);
    }

    /**
     * Returns list for cget.
     *
     * @param string $locale
     *
     * @return ListRepresentation
     */
    private function getList(Request $request, $locale)
    {
        $fieldDescriptors = $this->getFieldDescriptors();
        $listBuilder = $this->listBuilderFactory->create($this->contactClass);
        $listBuilder->addGroupBy($fieldDescriptors['id']);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $account = $request->get('accountId');
        if ($account) {
            $listBuilder->where($fieldDescriptors['accountId'], $account);
        }

        $listResponse = $this->prepareListResponse($listBuilder, $locale);

        return new ListRepresentation(
            $listResponse,
            ContactInterface::RESOURCE_KEY,
            'sulu_contact.get_contacts',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    /**
     * Prepare list response.
     *
     * @param string $locale
     *
     * @return array
     */
    private function prepareListResponse(DoctrineListBuilder $listBuilder, $locale)
    {
        $listResponse = $listBuilder->execute();
        $listResponse = $this->addAvatars($listResponse, $locale);

        $ids = $listBuilder->getIds();
        if (null !== $ids) {
            // the @ is necessary in case of a PHP bug https://bugs.php.net/bug.php?id=50688
            @\usort(
                $listResponse,
                function($a, $b) use ($ids) {
                    return $this->indexComparator->compare($a['id'], $b['id'], $ids);
                }
            );
        }

        return $listResponse;
    }

    /**
     * Deletes a Contact with the given ID from database.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        try {
            $deleteCallback = $this->contactManager->delete();
            $view = $this->responseDelete($id, $deleteCallback);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Shows the contact with the given Id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function($id) use ($locale) {
                    return $this->contactManager->getById($id, $locale);
                }
            );

            $context = new Context();
            $context->setGroups(static::$contactSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new contact.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $contact = $this->contactManager->save(
                $request->request->all()
            );
            $apiContact = $this->contactManager->getContact(
                $contact,
                $this->getLocale($request)
            );
            $view = $this->view($apiContact, 200);
            $context = new Context();
            $context->setGroups(static::$contactSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (MissingArgumentException $maex) {
            $view = $this->view($maex->toArray(), 400);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function putAction($id, Request $request)
    {
        try {
            $contact = $this->contactManager->save($request->request->all(), $id);

            $apiContact = $this->contactManager->getContact($contact, $this->getUser()->getLocale());
            $view = $this->view($apiContact, 200);
            $context = new Context();
            $context->setGroups(static::$contactSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Partially update an existing contact.
     *
     * @param int $id
     *
     * @return Response
     */
    public function patchAction($id, Request $request)
    {
        try {
            $contact = $this->contactManager->save(
                $request->request->all(),
                $id,
                true
            );

            $apiContact = $this->contactManager->getContact($contact, $this->getUser()->getLocale());
            $view = $this->view($apiContact, 200);
            $context = new Context();
            $context->setGroups(static::$contactSerializationGroups);
            $view->setContext($context);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a list of contacts which have a user in the sulu system.
     */
    protected function getContactsByUserSystem()
    {
        $users = $this->userRepository->findUserBySystem($this->suluSecuritySystem);
        $contacts = [];

        foreach ($users as $user) {
            $contacts[] = $user->getContact();
        }

        return $contacts;
    }

    public function getSecurityContext()
    {
        return 'sulu.contact.people';
    }

    /**
     * Takes an array of contacts and resets the avatar containing the media id with
     * the actual urls to the avatars thumbnail.
     *
     * @param array $contacts
     * @param string $locale
     *
     * @return array
     */
    private function addAvatars($contacts, $locale)
    {
        $ids = \array_filter(\array_column($contacts, 'avatar'));
        $avatars = $this->mediaManager->getFormatUrls($ids, $locale);
        foreach ($contacts as $key => $contact) {
            if (\array_key_exists('avatar', $contact)
                && $contact['avatar']
                && \array_key_exists($contact['avatar'], $avatars)
            ) {
                $contacts[$key]['avatar'] = $avatars[$contact['avatar']];
            }
        }

        return $contacts;
    }

    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136

    private function checkArguments(Request $request)
    {
        if (null === $request->get('firstName')) {
            throw new MissingArgumentException($this->contactClass, 'firstName');
        }
        if (null === $request->get('lastName')) {
            throw new MissingArgumentException($this->contactClass, 'lastName');
        }
        if (null === $request->get('formOfAddress')) {
            throw new MissingArgumentException($this->contactClass, 'formOfAddress');
        }
    }
}
