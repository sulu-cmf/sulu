<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use DateTime;
use Hateoas\Configuration\Exclusion;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ContactBundle\Api\Contact as ApiContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\RestController;
use FOS\RestBundle\Routing\ClassResourceInterface;

/**
 * Makes contacts available through a REST API.
 */
class ContactController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluContactBundle:Contact';
    protected static $entityKey = 'contacts';
    protected static $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected static $titleEntityName = 'SuluContactBundle:ContactTitle';
    protected static $positionEntityName = 'SuluContactBundle:Position';
    protected static $addressEntityName = 'SuluContactBundle:Address';
    protected static $contactAddressEntityName = 'SuluContactBundle:ContactAddress';

    // serialization groups for contact
    protected static $contactSerializationGroups = array(
        'fullContact',
        'partialAccount',
        'partialTag',
        'partialMedia',
        'partialCategory',
    );

    /**
     * @var string
     */
    protected $basePath = 'admin/api/contacts';

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'contact.contacts.';

    // TODO: move the field descriptors to a manager
    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    protected $accountContactFieldDescriptors;

    /**
     * @return RestHelperInterface
     */
    protected function getRestHelper()
    {
        return $this->get('sulu_core.doctrine_rest_helper');
    }

    protected function getFieldDescriptors()
    {
        if ($this->fieldDescriptors === null) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    protected function getAccountContactFieldDescriptors()
    {
        if ($this->accountContactFieldDescriptors === null) {
            $this->initFieldDescriptors();
        }

        return $this->accountContactFieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = array();

        $this->fieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            array(
                new DoctrineFieldDescriptor('firstName', 'firstName', self::$entityName),
                new DoctrineFieldDescriptor('lastName', 'lastName', self::$entityName),
            ),
            'fullName',
            'public.name',
            ' ',
            true,
            false,
            '',
            '',
            '100px',
            false
        );

        $this->fieldDescriptors['firstName'] = new DoctrineFieldDescriptor(
            'firstName',
            'firstName',
            self::$entityName,
            'contact.contacts.firstName',
            array(),
            false,
            true,
            '',
            '',
            '100px'
        );

        $this->fieldDescriptors['lastName'] = new DoctrineFieldDescriptor(
            'lastName',
            'lastName',
            self::$entityName,
            'contact.contacts.lastName',
            array(),
            false,
            true,
            '',
            '',
            '100px'
        );

        $this->fieldDescriptors['mainEmail'] = new DoctrineFieldDescriptor(
            'mainEmail',
            'mainEmail',
            self::$entityName,
            'public.email',
            array(),
            false,
            true,
            '',
            '',
            '140px'
        );

        $this->fieldDescriptors['account'] = new DoctrineFieldDescriptor(
            'name',
            'account',
            $this->getAccountEntityName(),
            'contact.contacts.company',
            array(
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    self::$entityName . '.accountContacts',
                    self::$accountContactEntityName . '.main = true',
                    'LEFT'
                ),
                $this->getAccountEntityName() => new DoctrineJoinDescriptor(
                    $this->getAccountEntityName(),
                    self::$accountContactEntityName . '.account'
                ),
            ),
            false,
            true
        );

        $this->fieldDescriptors['city'] = new DoctrineFieldDescriptor(
            'city',
            'city',
            self::$addressEntityName,
            'contact.address.city',
            array(
                self::$contactAddressEntityName => new DoctrineJoinDescriptor(
                    self::$contactAddressEntityName,
                    self::$entityName . '.contactAddresses',
                    self::$contactAddressEntityName . '.main = true',
                    'LEFT'
                ),
                self::$addressEntityName => new DoctrineJoinDescriptor(
                    self::$addressEntityName,
                    self::$contactAddressEntityName . '.address'
                ),
            ),
            false,
            true
        );

        $this->fieldDescriptors['mainPhone'] = new DoctrineFieldDescriptor(
            'mainPhone',
            'mainPhone',
            self::$entityName,
            'public.phone',
            array(),
            false,
            true
        );

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'public.id',
            array(),
            true,
            false,
            '',
            '50px'
        );

        $this->fieldDescriptors['mainFax'] = new DoctrineFieldDescriptor(
            'mainFax',
            'mainFax',
            self::$entityName,
            'public.fax',
            array(),
            true
        );

        $this->fieldDescriptors['mainUrl'] = new DoctrineFieldDescriptor(
            'mainUrl',
            'mainUrl',
            self::$entityName,
            'public.url',
            array(),
            true
        );

        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$entityName,
            'public.created',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$entityName,
            'public.changed',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['disabled'] = new DoctrineFieldDescriptor(
            'disabled',
            'disabled',
            self::$entityName,
            'public.deactivate',
            array(),
            true
        );

        $this->fieldDescriptors['birthday'] = new DoctrineFieldDescriptor(
            'birthday',
            'birthday',
            self::$entityName,
            'contact.contacts.birthday',
            array(),
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$titleEntityName,
            'public.title',
            array(
                self::$titleEntityName => new DoctrineJoinDescriptor(
                    self::$titleEntityName,
                    self::$entityName . '.title'
                ),
            ),
            true
        );

        $this->fieldDescriptors['salutation'] = new DoctrineFieldDescriptor(
            'salutation',
            'salutation',
            self::$entityName,
            'contact.contacts.salutation',
            array(),
            true
        );

        $this->fieldDescriptors['formOfAddress'] = new DoctrineFieldDescriptor(
            'formOfAddress',
            'formOfAddress',
            self::$entityName,
            'contact.contacts.formOfAddress',
            array(),
            true
        );

        $this->fieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            array(
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    self::$entityName . '.accountContacts'
                ),
                self::$positionEntityName => new DoctrineJoinDescriptor(
                    self::$positionEntityName,
                    self::$accountContactEntityName . '.position'
                ),
            ),
            true,
            false,
            '',
            '',
            '',
            false
        );

        // field descriptors for the account contact list
        $this->accountContactFieldDescriptors = array();
        $this->accountContactFieldDescriptors['id'] = $this->fieldDescriptors['id'];
        $this->accountContactFieldDescriptors['fullName'] = new DoctrineConcatenationFieldDescriptor(
            array(
                new DoctrineFieldDescriptor('firstName', 'firstName', self::$entityName),
                new DoctrineFieldDescriptor('lastName', 'lastName', self::$entityName),
            ),
            'fullName',
            'public.name',
            ' ',
            false,
            true,
            '',
            '',
            '100px',
            false
        );
        $this->accountContactFieldDescriptors['position'] = new DoctrineFieldDescriptor(
            'position',
            'position',
            self::$positionEntityName,
            'contact.contacts.position',
            array(
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    self::$entityName . '.accountContacts'
                ),
                self::$positionEntityName => new DoctrineJoinDescriptor(
                    self::$positionEntityName,
                    self::$accountContactEntityName . '.position'
                ),
            ),
            false,
            true,
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
            array(
                self::$accountContactEntityName => new DoctrineJoinDescriptor(
                    self::$accountContactEntityName,
                    self::$entityName . '.accountContacts'
                ),
            ),
            false,
            true,
            'radio',
            '',
            '',
            false
        );
    }

    /**
     * returns all fields that can be used by list.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction(Request $request)
    {
        if (!!$request->get('accountContacts')) {
            return $this->handleView($this->view(array_values($this->getAccountContactFieldDescriptors()), 200));
        }

        // default contacts list
        return $this->handleView($this->view(array_values($this->getFieldDescriptors()), 200));
    }

    /**
     * lists all contacts
     * optional parameter 'flat' calls listAction.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $serializationGroups = array();
        $locale = $this->getLocale($request);

        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->getRestHelper();

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_contacts',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            if ($request->get('bySystem') == true) {
                $contacts = $this->getContactsByUserSystem();
                $serializationGroups[] = 'select';
            } else {
                $contacts = $this->getDoctrine()->getRepository(self::$entityName)->findAll();
                $serializationGroups = array_merge(
                    $serializationGroups,
                    static::$contactSerializationGroups
                );
            }
            // convert to api-contacts
            $apiContacts = array();
            foreach ($contacts as $contact) {
                $apiContacts[] = new ApiContact($contact, $locale);
            }

            $exclusion = null;
            if (count($serializationGroups) > 0) {
                $exclusion = new Exclusion($serializationGroups);
            }

            $list = new CollectionRepresentation($apiContacts, self::$entityKey, null, $exclusion, $exclusion);
        }

        $view = $this->view($list, 200);

        // set serialization groups
        if (count($serializationGroups) > 0) {
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    $serializationGroups
                )
            );
        }

        return $this->handleView($view);
    }

    /**
     * Deletes a Contact with the given ID from database.
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        try {
            $delete = $this->getContactManager()->delete($id);
            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Shows the contact with the given Id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $contactManager = $this->getContactManager();
        $locale = $this->getUser()->getLocale();

        try {
            $view = $this->responseGetById(
                $id,
                function ($id) use ($contactManager, $locale) {
                    return $contactManager->getById($id, $locale);
                }
            );

            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new contact.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $contact = $this->getContactManager()->save(
                $request->request->all()
            );
            $apiContact = $this->getContactManager()->getContact(
                $contact,
                $this->getLocale($request)
            );
            $view = $this->view($apiContact, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );

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
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        $contactEntity = 'SuluContactBundle:Contact';

        try {
            $contact = $this->getContactManager()->save(
                $request->request->all(),
                $id
            );

            $apiContact = $this->getContactManager()->getContact($contact, $this->getUser()->getLocale());
            $view = $this->view($apiContact, 200);
            $view->setSerializationContext(
                SerializationContext::create()->setGroups(
                    static::$contactSerializationGroups
                )
            );
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @return AbstractContactManager
     */
    protected function getContactManager()
    {
        return $this->get('sulu_contact.contact_manager');
    }

    /**
     * Returns a list of contacts which have a user in the sulu system.
     */
    protected function getContactsByUserSystem()
    {
        $repo = $this->get('sulu_security.user_repository');
        $users = $repo->getUserInSystem();
        $contacts = [];

        foreach ($users as $user) {
            $contacts[] = $user->getContact();
        }

        return $contacts;
    }

    private function getAccountEntityName()
    {
        return $this->container->getParameter('sulu_contact.account.entity');
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.contact.people';
    }

    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136
    private function checkArguments(Request $request)
    {
        if ($request->get('firstName') == null) {
            throw new MissingArgumentException(static::$entityName, 'username');
        }
        if ($request->get('lastName') === null) {
            throw new MissingArgumentException(static::$entityName, 'password');
        }
        if (is_null($request->get('disabled'))) {
            throw new MissingArgumentException(static::$entityName, 'disabled');
        }
        if ($request->get('formOfAddress') == null) {
            throw new MissingArgumentException(static::$entityName, 'contact');
        }
    }
}
