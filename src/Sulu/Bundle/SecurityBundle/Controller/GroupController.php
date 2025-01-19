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
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the groups accessible through a REST-API.
 *
 * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
 */
class GroupController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = Group::class;

    protected static $entityKey = 'groups';

    // TODO: Create a Manager and move the field descriptors to the manager

    /**
     * @var array - Holds the field descriptors for the list response
     */
    protected $fieldDescriptors;

    public const ENTITY_NAME_ROLE = RoleInterface::class;

    // TODO: move the field descriptors to a manager
    public function __construct(
        ViewHandlerInterface $viewHandler,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        private RoleRepositoryInterface $roleRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($viewHandler);

        $this->fieldDescriptors = [];
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', static::$entityName);
        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor('name', 'name', static::$entityName);
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor('created', 'created', static::$entityName);
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor('changed', 'changed', static::$entityName);
    }

    /**
     * returns all groups.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        if ('true' == $request->get('flat')) {
            $listBuilder = $this->doctrineListBuilderFactory->create(static::$entityName);

            $this->restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            $list = new PaginatedRepresentation(
                $listBuilder->execute(),
                static::$entityKey,
                (int) $listBuilder->getCurrentPage(),
                (int) $listBuilder->getLimit(),
                (int) $listBuilder->count()
            );
        } else {
            $list = new CollectionRepresentation(
                $this->entityManager->getRepository(static::$entityName)->findAllGroups(),
                static::$entityKey
            );
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns the group with the given id.
     *
     * @return Response
     */
    public function getAction($id)
    {
        $find = function($id) {
            /** @var Group $group */
            $group = $this->entityManager->getRepository(static::$entityName)->findGroupById($id);

            return $group;
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new group with the given data.
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        if (null != $name) {
            $group = new Group();
            $group->setName($name);

            $this->setParent($group, $request);

            $roles = $request->get('roles');
            if (!empty($roles)) {
                foreach ($roles as $roleData) {
                    $this->addRole($group, $roleData);
                }
            }

            $this->entityManager->persist($group);
            $this->entityManager->flush();

            $view = $this->view($group, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the group with the given id and the data given by the request.
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var Group $group */
        $group = $this->entityManager->getRepository(static::$entityName)->findGroupById($id);

        try {
            if (!$group) {
                throw new EntityNotFoundException(static::$entityName, $id);
            } else {
                $name = $request->get('name');

                $group->setName($name);

                $this->setParent($group, $request);

                if (!$this->processRoles($group, $request->get('roles', []))) {
                    throw new RestException('Could not update dependencies!');
                }

                $this->entityManager->flush();
                $view = $this->view($group, 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Process all roles from request.
     *
     * @param Group $group The contact on which is worked
     * @param array $roles The roles to process
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processRoles(Group $group, $roles)
    {
        $get = function($entity) {
            /* @var RoleInterface $entity */
            return $entity->getId();
        };

        $delete = function($role) {
            $this->entityManager->remove($role);
        };

        $update = function($role, $roleData) {
            return $this->updateRole($role, $roleData);
        };

        $add = function($role) use ($group) {
            return $this->addRole($group, $role);
        };

        return $this->restHelper->processSubEntities($group->getRoles(), $roles, $get, $add, $update, $delete);
    }

    /**
     * Deletes the group with the given id.
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            $group = $this->entityManager->getRepository(static::$entityName)->findGroupById($id);

            if (!$group) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            $this->entityManager->remove($group);
            $this->entityManager->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Adds the given role to the group.
     *
     * @param array $roleData
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    private function addRole(Group $group, $roleData)
    {
        if (isset($roleData['id'])) {
            $role = $this->roleRepository->findRoleById($roleData['id']);

            if (!$role) {
                throw new EntityNotFoundException($this->roleRepository->getClassName(), $roleData['id']);
            }

            if (!$group->getRoles()->contains($role)) {
                $group->addRole($role);
            }
        }

        return true;
    }

    /**
     * Updates an already existing role.
     *
     * @return bool
     */
    private function updateRole(RoleInterface $role, $roleData)
    {
        // no action on update
        return true;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function setParent($group, Request $request)
    {
        $parentData = $request->get('parent');
        if (null != $parentData && isset($parentData['id'])) {
            $parent = $this->entityManager->getRepository(static::$entityName)->findGroupById($parentData['id']);

            if (!$parent) {
                throw new EntityNotFoundException(static::$entityName, $parentData['id']);
            }
            $group->setParent($parent, $request);
        }
    }

    /**
     * Returns the SecurityContext required for the controller.
     */
    public function getSecurityContext()
    {
        return 'sulu.security.groups';
    }
}
