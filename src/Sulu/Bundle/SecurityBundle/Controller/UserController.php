<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the users accessible through a rest api.
 */
class UserController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * @deprecated Use the UserInterface::RESOURCE_KEY constant instead
     */
    protected static $entityKey = 'users';

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        private UserManager $userManager,
        private EntityManagerInterface $entityManager,
        private string $userClass
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Contains the field descriptors used by the list response.
     * TODO: move field descriptors to a manager.
     *
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    protected function getFieldDescriptors()
    {
        if (empty($this->fieldDescriptors)) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = [];
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            $this->userClass
        );
        $this->fieldDescriptors['username'] = new DoctrineFieldDescriptor(
            'username',
            'username',
            $this->userClass
        );
        $this->fieldDescriptors['email'] = new DoctrineFieldDescriptor(
            'email',
            'email',
            $this->userClass
        );
        $this->fieldDescriptors['locale'] = new DoctrineFieldDescriptor(
            'locale',
            'locale',
            $this->userClass
        );
        $this->fieldDescriptors['apiKey'] = new DoctrineFieldDescriptor(
            'apiKey',
            'apiKey',
            $this->userClass
        );
    }

    /**
     * Returns the user with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $find = function($id) {
            return $this->userManager->getUserById($id);
        };

        $view = $this->responseGetById($id, $find);

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Creates a new user in the system.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $locale = $this->getRequestParameter($request, 'locale', true);
            $data = $request->request->all();
            $data['contactId'] = $request->query->get('contactId');
            $user = $this->userManager->save($data, $locale);
            $view = $this->view($user, 200);
        } catch (MissingPasswordException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * @param int $id
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $request->get('action');

        try {
            $user = match ($action) {
                'enable' => $this->userManager->enableUser($id),
                'lock' => $this->userManager->lockUser($id),
                'unlock' => $this->userManager->unlockUser($id),
                default => throw new RestException('Unrecognized action: ' . $action),
            };

            // prepare view
            $view = $this->view($user, 200);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Updates the given user with the given data.
     *
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            $this->checkArguments($request);
            $locale = $this->getRequestParameter($request, 'locale', true);
            $user = $this->userManager->save($request->request->all(), $locale, $id);
            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Partly updates a user entity for a given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function patchAction(Request $request, $id)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale');
            $user = $this->userManager->save($request->request->all(), $locale, $id, true);
            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = $this->userManager->delete();
        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Checks if all the arguments are given, and throws an exception if one is missing.
     *
     * @throws MissingArgumentException
     */
    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136
    private function checkArguments(Request $request)
    {
        if (null == $request->get('username')) {
            throw new MissingArgumentException($this->userClass, 'username');
        }
        if ($request->isMethod('POST') && null === $request->get('password')) {
            throw new MissingArgumentException($this->userClass, 'password');
        }
        if (null == $request->get('locale')) {
            throw new MissingArgumentException($this->userClass, 'locale');
        }
        if (null == $request->get('contact') && null == $request->get('contactId')) {
            throw new MissingArgumentException($this->userClass, 'contact');
        }
    }

    /**
     * Returns a user with a specific contact id or all users
     * optional parameter 'flat' calls listAction.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $view = null;
        if ('true' == $request->get('flat')) {
            $listBuilder = $this->doctrineListBuilderFactory->create($this->userClass);

            $this->restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new PaginatedRepresentation(
                $listBuilder->execute(),
                UserInterface::RESOURCE_KEY,
                (int) $listBuilder->getCurrentPage(),
                (int) $listBuilder->getLimit(),
                (int) $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $contactId = $request->get('contactId');

            if (null != $contactId) {
                $user = $this->entityManager->getRepository($this->userClass)->findUserByContact($contactId);

                $view = $this->view($user ?? new \stdClass(), 200);
            } else {
                $entities = $this->userManager->findAll();
                $list = new CollectionRepresentation($entities, UserInterface::RESOURCE_KEY);
                $view = $this->view($list, 200);
            }
        }

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    public function getSecurityContext()
    {
        return 'sulu.security.users';
    }

    /**
     * Adds the necessary serialization groups to the given view.
     */
    private function addSerializationGroups($view)
    {
        $context = new Context();

        // set serialization groups
        $view->setContext($context->setGroups(['Default', 'partialContact', 'fullUser']));
    }
}
