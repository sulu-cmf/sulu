<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Controller\PermissionController;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PermissionControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PermissionController
     */
    private $permissionController;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<ViewHandlerInterface>
     */
    private $viewHandler;

    /**
     * @var array
     */
    private $resources;

    public function setUp(): void
    {
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->resources = [
            'example' => [
                'security_context' => 'sulu_example.example',
                'security_class' => 'Acme\Example',
            ],
            'pages' => [
                'security_context' => 'sulu_page.#webspace#',
                'security_class' => 'Acme\Page',
            ],
        ];

        $this->permissionController = new PermissionController(
            $this->accessControlManager->reveal(),
            $this->securityChecker->reveal(),
            $this->roleRepository->reveal(),
            $this->viewHandler->reveal(),
            $this->resources
        );
    }

    public static function providePermissionData()
    {
        return [
            [
                '1',
                'example',
                [
                    'add' => 'true',
                    'view' => true,
                    'delete' => false,
                    'edit' => 'false',
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePermissionData')]
    public function testGetAction($id, $resourceKey, $permissions): void
    {
        $request = new Request(['id' => $id, 'resourceKey' => $resourceKey]);
        $this->accessControlManager->getPermissions($this->resources[$resourceKey]['security_class'], $id)
            ->willReturn([1 => $permissions]);

        $this->viewHandler->handle(
            View::create(
                [
                    'permissions' => [1 => $permissions],
                ]
            )
        )->shouldBeCalled();

        $this->permissionController->cgetAction($request);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePermissionData')]
    public function testPutAction($id, $resourceKey, $permissions): void
    {
        $request = new Request(
            [
                'id' => $id,
                'resourceKey' => $resourceKey,
            ],
            [
                'permissions' => [
                    1 => $permissions,
                ],
            ]
        );

        \array_walk(
            $permissions,
            function(&$permissionLine): void {
                $permissionLine = 'true' === $permissionLine || true === $permissionLine;
            }
        );

        $this->viewHandler->handle(
            View::create(
                [
                    'permissions' => [
                        1 => $permissions,
                    ],
                ]
            )
        )->shouldBeCalled();

        $this->permissionController->cputAction($request);
    }

    public static function provideWrongPermissionData()
    {
        return [
            [null, null, null],
            ['1', null, []],
            [null, 'Acme\Example', []],
            ['1', 'Acme\Example', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideWrongPermissionData')]
    public function testPutActionWithWrongData($id, $class, $permissions): void
    {
        $request = new Request(
            [
                'id' => $id,
                'type' => $class,
            ],
            [
                'permissions' => $permissions,
            ]
        );

        $this->accessControlManager->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->permissionController->cputAction($request);
    }

    public function testPutActionWithInheritance(): void
    {
        $request = new Request(
            [
                'id' => 5,
                'resourceKey' => 'example',
                'inherit' => true,
            ],
            [
                'permissions' => [],
            ]
        );

        $this->accessControlManager->setPermissions('Acme\\Example', 5, [], true)->shouldBeCalled();

        $this->permissionController->cputAction($request);
    }

    public function testPutActionWithMissingPermissionsAndWebspace(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->securityChecker->checkPermission('sulu_page.example', 'security')
             ->willThrow(AccessDeniedException::class);

        $request = new Request(
            [
                'id' => 1,
                'resourceKey' => 'pages',
                'webspace' => 'example',
            ],
            [
                'permissions' => [
                    1 => [
                        'add' => true,
                        'view' => true,
                        'delete' => false,
                        'edit' => false,
                        'archive' => false,
                        'live' => false,
                        'security' => false,
                    ],
                ],
            ]
        );

        $this->viewHandler->handle(Argument::cetera())->shouldNotBeCalled();

        $this->permissionController->cputAction($request);
    }

    public function testPutActionWithPermissionsAndWebspace(): void
    {
        $this->securityChecker->checkPermission('sulu_page.example', 'security')->willReturn(true);

        $request = new Request(
            [
                'id' => 1,
                'resourceKey' => 'pages',
                'webspace' => 'example',
            ],
            [
                'permissions' => [
                    1 => [
                        'add' => true,
                        'view' => true,
                        'delete' => false,
                        'edit' => false,
                        'archive' => false,
                        'live' => false,
                        'security' => false,
                    ],
                ],
            ]
        );

        $this->viewHandler->handle(Argument::cetera())->shouldBeCalled();

        $this->permissionController->cputAction($request);
    }
}
