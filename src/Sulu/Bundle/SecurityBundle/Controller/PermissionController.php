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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller handles all object based securities.
 */
class PermissionController implements ClassResourceInterface
{
    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        AccessControlManagerInterface $accessControlManager,
        SecurityCheckerInterface $securityChecker,
        RoleRepositoryInterface $roleRepository,
        ViewHandlerInterface $viewHandler,
        array $resources
    ) {
        $this->accessControlManager = $accessControlManager;
        $this->securityChecker = $securityChecker;
        $this->roleRepository = $roleRepository;
        $this->viewHandler = $viewHandler;
        $this->resources = $resources;
    }

    public function cgetAction(Request $request)
    {
        try {
            $identifier = $request->get('id');
            $resourceKey = $request->get('resourceKey');

            if (!$identifier) {
                throw new MissingParameterException(static::class, 'id');
            }

            if (!$resourceKey) {
                throw new MissingParameterException(static::class, 'resourceKey');
            }

            if (!$this->resources[$resourceKey]) {
                throw new RestException('The resourceKey "' . $resourceKey . '" does not exist!');
            }

            $permissions = $this->accessControlManager->getPermissions(
                $this->resources[$resourceKey]['security_class'],
                $identifier
            );

            return $this->viewHandler->handle(View::create(
                [
                    'permissions' => $permissions,
                ]
            ));
        } catch (RestException $exc) {
            return $this->viewHandler->handle(View::create($exc->toArray(), 400));
        }
    }

    public function cputAction(Request $request)
    {
        try {
            $identifier = $request->get('id');
            $resourceKey = $request->get('resourceKey');
            $permissions = $request->get('permissions');
            $securityContext = $request->get('securityContext'); // TODO don't rely on securityContext passed in request

            if (!$identifier) {
                throw new MissingParameterException(static::class, 'id');
            }

            if (!$resourceKey) {
                throw new MissingParameterException(static::class, 'resourceKey');
            }

            if (!is_array($permissions)) {
                throw new RestException('The "permissions" must be passed as an array');
            }

            if (!$this->resources[$resourceKey]) {
                throw new RestException('The resourceKey "' . $resourceKey . '" does not exist!');
            }

            if ($securityContext) {
                $this->securityChecker->checkPermission($securityContext, PermissionTypes::SECURITY);
            }

            // transfer all permission strings to booleans
            foreach ($permissions as &$permission) {
                array_walk($permission, function(&$permissionLine) {
                    $permissionLine = 'true' === $permissionLine || true === $permissionLine;
                });
            }

            $this->accessControlManager->setPermissions(
                $this->resources[$resourceKey]['security_class'],
                $identifier,
                $permissions
            );

            return $this->viewHandler->handle(View::create([
                'permissions' => $permissions,
            ]));
        } catch (RestException $exc) {
            return $this->viewHandler->handle(View::create($exc->toArray(), 400));
        }
    }
}
