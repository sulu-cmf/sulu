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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\Domain\Event\RoleCreatedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\RoleModifiedEvent;
use Sulu\Bundle\SecurityBundle\Domain\Event\RoleRemovedEvent;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Exception\RoleKeyAlreadyExistsException;
use Sulu\Bundle\SecurityBundle\Exception\RoleNameAlreadyExistsException;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the roles accessible through a REST-API.
 */
class RoleController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    public const ENTITY_NAME_PERMISSION = Permission::class;

    protected $bundlePrefix = 'security.roles.';

    /**
     * @var array - Holds the field descriptors for the list response
     */
    protected $fieldDescriptors = [];

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private RestHelperInterface $restHelper,
        private DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        private MaskConverterInterface $maskConverter,
        private RoleRepositoryInterface $roleRepository,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $eventCollector,
        private string $roleClass
    ) {
        parent::__construct($viewHandler);
    }

    protected function getFieldDescriptors()
    {
        if (empty($this->fieldDescriptors)) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->fieldDescriptorFactory
             ->getFieldDescriptors('roles');
    }

    /**
     * returns all roles.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        if ('true' == $request->query->get('flat')) {
            $listBuilder = $this->doctrineListBuilderFactory->create($this->roleClass);
            $fieldDescriptor = $this->getFieldDescriptors();

            if (!$request->query->getBoolean('include-anonymous')) {
                $listBuilder->where($fieldDescriptor['anonymous'], false);
            }

            $this->restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new PaginatedRepresentation(
                $listBuilder->execute(),
                RoleInterface::RESOURCE_KEY,
                (int) $listBuilder->getCurrentPage(),
                (int) $listBuilder->getLimit(),
                (int) $listBuilder->count()
            );
        } else {
            $filter = [];
            if (!$request->query->getBoolean('include-anonymous')) {
                $filter['anonymous'] = false;
            }
            $roles = $this->roleRepository->findAllRoles($filter);
            $convertedRoles = [];
            if (null != $roles) {
                foreach ($roles as $role) {
                    \array_push($convertedRoles, $this->convertRole($role));
                }
            }
            $list = new CollectionRepresentation($convertedRoles, RoleInterface::RESOURCE_KEY);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns the role with the given id.
     *
     * @return Response
     */
    public function getAction($id)
    {
        $find = function($id) {
            /** @var RoleInterface $role */
            $role = $this->roleRepository->findRoleById($id);

            return $this->convertRole($role);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new role with the given data.
     *
     * @return Response
     *
     * @throws EntityIdAlreadySetException
     * @throws EntityNotFoundException
     */
    public function postAction(Request $request)
    {
        $name = $request->request->get('name');
        $key = $request->request->get('key');
        $system = $request->request->get('system');

        try {
            if (null === $name) {
                throw new InvalidArgumentException('Role', 'name');
            }
            if (null === $system) {
                throw new InvalidArgumentException('Role', 'system');
            }

            /** @var RoleInterface $role */
            $role = $this->roleRepository->createNew();
            $role->setName($name);
            $role->setKey($key);
            $role->setSystem($system);

            $permissions = $request->request->all('permissions');

            if (!empty($permissions)) {
                foreach ($permissions as $permissionData) {
                    $this->addPermission($role, $permissionData);
                }
            }

            $securityTypeData = $request->request->all('securityType');
            if ($this->checkSecurityTypeData($securityTypeData)) {
                $this->setSecurityType($role, $securityTypeData);
            }

            try {
                $this->entityManager->persist($role);
                $this->eventCollector->collect(new RoleCreatedEvent($role, $request->request->all()));
                $this->entityManager->flush();

                $view = $this->view($this->convertRole($role), 200);
            } catch (UniqueConstraintViolationException $e) {
                if (\strpos($e->getMessage(), 'Duplicate entry \'' . $role->getName())) {
                    throw new RoleNameAlreadyExistsException($name, $e);
                } else {
                    throw new RoleKeyAlreadyExistsException($key, $e);
                }
            }
        } catch (RestException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the role with the given id and the data given by the request.
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var RoleInterface $role */
        $role = $this->roleRepository->findRoleById($id);
        $name = $request->request->get('name');
        $key = $request->request->get('key');
        $system = $request->request->get('system');

        try {
            if (!$role) {
                throw new EntityNotFoundException($this->roleRepository->getClassName(), $id);
            } else {
                $role->setName($name);
                $role->setKey($key);
                $role->setSystem($system);

                if (!$this->processPermissions($role, $request->request->all('permissions'))) {
                    throw new RestException('Could not update dependencies!');
                }

                $securityTypeData = $request->request->all('securityType');
                if ($this->checkSecurityTypeData($securityTypeData)) {
                    $this->setSecurityType($role, $securityTypeData);
                } else {
                    $role->setSecurityType(null);
                }

                $this->eventCollector->collect(new RoleModifiedEvent($role, $request->request->all()));
                $this->entityManager->flush();
                $view = $this->view($this->convertRole($role), 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (UniqueConstraintViolationException $e) {
            if (\strpos($e->getMessage(), 'Duplicate entry \'' . $role->getName())) {
                throw new RoleNameAlreadyExistsException($name, $e);
            } else {
                throw new RoleKeyAlreadyExistsException($key, $e);
            }
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the role with the given id.
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            $role = $this->roleRepository->findRoleById($id);

            if (!$role) {
                throw new EntityNotFoundException($this->roleRepository->getClassName(), $id);
            }

            $this->eventCollector->collect(new RoleRemovedEvent($id, $role->getName()));

            foreach ($role->getPermissions() as $permission) {
                $this->entityManager->detach($permission);
            }

            $this->entityManager->remove($role);
            $this->entityManager->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all permissions from request.
     *
     * @param RoleInterface $role The contact on which is worked
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPermissions(RoleInterface $role, $permissions)
    {
        $get = function($entity) {
            /* @var Permission $entity */

            return $entity->getId();
        };

        $delete = function($permission) {
            $this->entityManager->remove($permission);
        };

        $update = function($permission, $permissionData) {
            return $this->updatePermission($permission, $permissionData);
        };

        $add = function($permission) use ($role) {
            return $this->addPermission($role, $permission);
        };

        return $this->restHelper->processSubEntities(
            $role->getPermissions(), $permissions, $get, $add, $update, $delete
        );
    }

    /**
     * Adds a permission to the given role.
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function addPermission(RoleInterface $role, $permissionData)
    {
        $alreadyContains = false;

        if (isset($permissionData['id'])) {
            $permission = $this->entityManager->getRepository(static::ENTITY_NAME_PERMISSION)->find($permissionData['id']);
            if (!$permission) {
                throw new EntityNotFoundException(static::ENTITY_NAME_PERMISSION, $permissionData['id']);
            }
            // only add if not already contains
            $alreadyContains = $role->getPermissions()->contains($permission);
        } else {
            $permission = new Permission();
            $permission->setContext($permissionData['context']);
            $permission->setPermissions(
                $this->maskConverter->convertPermissionsToNumber($permissionData['permissions'])
            );
        }
        if (false === $alreadyContains) {
            $permission->setRole($role);
            $this->entityManager->persist($permission);
            $role->addPermission($permission);
        }

        return true;
    }

    /**
     * Updates an already existing permission.
     *
     * @return bool
     */
    private function updatePermission(Permission $permission, $permissionData)
    {
        $permission->setContext($permissionData['context']);

        $permission->setPermissions(
            $this->maskConverter->convertPermissionsToNumber($permissionData['permissions'])
        );

        return true;
    }

    /**
     * Converts a role object into an array for the rest service.
     *
     * @return array
     */
    protected function convertRole(RoleInterface $role)
    {
        $roleData['id'] = $role->getId();
        $roleData['name'] = $role->getName();
        $roleData['key'] = $role->getKey();
        $roleData['identifier'] = $role->getIdentifier();
        $roleData['system'] = $role->getSystem();
        $roleData['permissions'] = [];

        $permissions = $role->getPermissions();
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                /* @var Permission $permission */
                $roleData['permissions'][] = [
                    'id' => $permission->getId(),
                    'context' => $permission->getContext(),
                    'permissions' => $this->maskConverter->convertPermissionsToArray($permission->getPermissions()),
                ];
            }
        }

        $securityType = $role->getSecurityType();
        if ($securityType) {
            $roleData['securityType'] = [
                'id' => $securityType->getId(),
                'name' => $securityType->getName(),
            ];
        }

        return $roleData;
    }

    /**
     * Checks if the data of the security type is correct.
     *
     * @return bool
     */
    private function checkSecurityTypeData($securityTypeData)
    {
        return null != $securityTypeData && null != $securityTypeData['id'] && '' != $securityTypeData['id'];
    }

    /**
     * Sets the securityType from the given data to the role.
     *
     * @param RoleInterface $role
     *
     * @throws EntityNotFoundException
     */
    private function setSecurityType($role, $securityTypeData)
    {
        $securityType = $this->entityManager
            ->getRepository(\Sulu\Bundle\SecurityBundle\Entity\SecurityType::class)
            ->findSecurityTypeById($securityTypeData['id']);

        if (!$securityType) {
            throw new EntityNotFoundException(\Sulu\Bundle\SecurityBundle\Entity\SecurityType::class, $securityTypeData['id']);
        }
        $role->setSecurityType($securityType);
    }

    public function getSecurityContext()
    {
        return 'sulu.security.roles';
    }
}
