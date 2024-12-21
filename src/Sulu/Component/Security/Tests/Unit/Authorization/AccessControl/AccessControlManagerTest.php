<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization\AccessControl;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Exception\AccessControlDescendantProviderNotFoundException;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlProviderInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\DescendantProviderInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

class AccessControlManagerTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    /**
     * @var AccessControlManager
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<MaskConverterInterface>
     */
    private $maskConverter;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var ObjectProphecy<DescendantProviderInterface>
     */
    private $descendantProvider1;

    /**
     * @var ObjectProphecy<DescendantProviderInterface>
     */
    private $descendantProvider2;

    /**
     * @var ObjectProphecy<SystemStoreInterface>
     */
    private $systemStore;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<AccessControlRepositoryInterface>
     */
    private $accessControlRepository;

    /**
     * @var ObjectProphecy<Security|SymfonyCoreSecurity>
     */
    private $security;

    /**
     * @var ObjectProphecy<UserInterface>
     */
    private $user;

    /**
     * @var array<string, int>
     */
    private $permissions = [
        'view' => 64,
        'add' => 32,
        'edit' => 16,
        'delete' => 8,
        'archive' => 4,
        'live' => 2,
        'security' => 1,
    ];

    public function setUp(): void
    {
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->systemStore = $this->prophesize(SystemStoreInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->accessControlRepository = $this->prophesize(AccessControlRepositoryInterface::class);
        $this->security = $this->prophesize(\class_exists(Security::class) ? Security::class : SymfonyCoreSecurity::class);
        $this->user = $this->prophesize(UserInterface::class);

        $this->security->getUser()->willReturn($this->user->reveal());

        $this->maskConverter->convertPermissionsToArray(0)->willReturn(['view' => false, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(32)->willReturn(['view' => false, 'edit' => true]);
        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(127)->willReturn([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ]);

        $this->descendantProvider1 = $this->prophesize(DescendantProviderInterface::class);
        $this->descendantProvider2 = $this->prophesize(DescendantProviderInterface::class);

        $this->accessControlManager = new AccessControlManager(
            $this->maskConverter->reveal(),
            $this->eventDispatcher->reveal(),
            $this->systemStore->reveal(),
            [
                $this->descendantProvider1->reveal(),
                $this->descendantProvider2->reveal(),
            ],
            $this->roleRepository->reveal(),
            $this->accessControlRepository->reveal(),
            $this->security->reveal(),
            $this->permissions
        );
    }

    public function testSetPermissions(): void
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->setPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->setPermissions(\stdClass::class, '1', [])->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent(\stdClass::class, '1', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', []);
    }

    public function testSetPermissionsWithInheritance(): void
    {
        $this->systemStore->getSystem()->willReturn('Sulu');
        $this->systemStore->getAnonymousRole()->willReturn(null);

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(Argument::any())->willReturn(true);
        $accessControlProvider->setPermissions(\stdClass::class, '1', [])->shouldBeCalled();
        $accessControlProvider->setPermissions(\stdClass::class, '2', [])->shouldBeCalled();
        $accessControlProvider->setPermissions(\stdClass::class, '3', [])->shouldBeCalled();
        $accessControlProvider->setPermissions(\stdClass::class, '5', [])->shouldBeCalled();

        $this->descendantProvider1->supportsDescendantType(\stdClass::class)->willReturn(true);
        $this->descendantProvider1->findDescendantIdsById('1')->willReturn(['2', '3', '5']);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());
        $this->accessControlRepository->findIdsWithGrantedPermissions(
            $this->user->reveal(),
            $this->permissions[PermissionTypes::SECURITY],
            \stdClass::class,
            ['2', '3', '5'],
            'Sulu',
            null
        )->willReturn(['2', '3', '5']);

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent(\stdClass::class, '2', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent(\stdClass::class, '3', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent(\stdClass::class, '5', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent(\stdClass::class, '1', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', [], true);
    }

    public function testSetPermissionsWithInheritanceWithoutDescendantProvider(): void
    {
        $this->expectException(AccessControlDescendantProviderNotFoundException::class);

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(Argument::any())->willReturn(true);
        $accessControlProvider->setPermissions(Argument::any())->shouldNotBeCalled();

        $this->descendantProvider1->supportsDescendantType(\stdClass::class)->willReturn(false);
        $this->descendantProvider2->supportsDescendantType(\stdClass::class)->willReturn(false);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', [], true);
    }

    public function testSetPermissionsWithInheritanceWithInsufficientDescendantPermissions(): void
    {
        $this->systemStore->getSystem()->willReturn('Sulu');
        $this->systemStore->getAnonymousRole()->willReturn(null);

        $this->expectException(InsufficientDescendantPermissionsException::class);

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(Argument::any())->willReturn(true);
        $accessControlProvider->setPermissions(Argument::any())->shouldNotBeCalled();

        $this->descendantProvider1->supportsDescendantType(\stdClass::class)->willReturn(true);
        $this->descendantProvider1->findDescendantIdsById('1')->willReturn(['2', '3', '5']);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());
        $this->accessControlRepository->findIdsWithGrantedPermissions(
            $this->user->reveal(),
            $this->permissions[PermissionTypes::SECURITY],
            \stdClass::class,
            ['2', '3', '5'],
            'Sulu',
            null
        )->willReturn(['2', '5']);

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', [], true);
    }

    public function testSetPermissionsWithoutProvider(): void
    {
        $this->assertNull($this->accessControlManager->setPermissions(\stdClass::class, '1', []));
    }

    public function testGetPermissions(): void
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->getPermissions(\stdClass::class, '1', null)->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->getPermissions(\stdClass::class, '1');
    }

    public function testGetPermissionsWithSystem(): void
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->getPermissions(\stdClass::class, '1', 'Sulu')->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->getPermissions(\stdClass::class, '1', 'Sulu');
    }

    public function testGetPermissionsWithoutProvider(): void
    {
        $this->assertNull($this->accessControlManager->getPermissions(\stdClass::class, '1'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUserPermission')]
    public function testGetUserPermissions(
        $rolePermissions,
        $securityContextPermissions,
        $userLocales,
        $locale,
        $result,
        $system
    ): void {
        $this->systemStore->getSystem()->willReturn($system);

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', $system)->willReturn($rolePermissions);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        // create role for given role permissions from data provider
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn($securityContextPermissions);
        $permission1->getContext()->willReturn('example');
        $role1 = $this->prophesize(Role::class);
        $role1->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $role1->getSystem()->willReturn($system);
        $role1->getId()->willReturn(1);
        $userRole1 = $this->prophesize(UserRole::class);
        $userRole1->getRole()->willReturn($role1->reveal());
        $userRole1->getLocales()->willReturn($userLocales);

        // add a role which should not influence the security context check
        $permission2 = $this->prophesize(Permission::class);
        $permission2->getPermissions()->willReturn(127);
        $permission2->getContext()->willReturn('not-important');
        $role2 = $this->prophesize(Role::class);
        $role2->getPermissions()->willReturn([$permission2->reveal()]);
        $role2->getSystem()->willReturn($system);
        $role2->getId()->willReturn(2);
        $userRole2 = $this->prophesize(UserRole::class);
        $userRole2->getRole()->willReturn($role2->reveal());
        $userRole2->getLocales()->willReturn($userLocales);

        // return the user with the above definitions
        $user = $this->prophesize(User::class);
        $user->getUserRoles()->willReturn([$userRole1->reveal(), $userRole2->reveal()]);
        $user->getRoleObjects()->willReturn([$role1->reveal(), $role2->reveal()]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', $locale, \stdClass::class, '1'),
            $user->reveal()
        );

        $this->assertEquals($result, $permissions);
    }

    public function testGetUserPermissionsWithMissingRole(): void
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        // create role for given role permissions from data provider
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $role1 = $this->prophesize(Role::class);
        $role1->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $role1->getSystem()->willReturn('Sulu');
        $role1->getId()->willReturn(1);
        $userRole1 = $this->prophesize(UserRole::class);
        $userRole1->getRole()->willReturn($role1->reveal());
        $userRole1->getLocales()->willReturn(['de', 'en']);

        // return the user with the above definitions
        $user = $this->prophesize(User::class);
        $user->getUserRoles()->willReturn([$userRole1->reveal()]);
        $user->getRoleObjects()->willReturn([$role1->reveal()]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            $user->reveal()
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testGetUserPermissionsWithoutUser(): void
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $anonymousRole = $this->prophesize(Role::class);
        $anonymousRole->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $anonymousRole->getSystem()->willReturn('Sulu');
        $anonymousRole->getId()->willReturn(1);

        $this->roleRepository->findAllRoles(['anonymous' => true])->willReturn([$anonymousRole]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            null
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testGetUserPermissionsWithoutSystem(): void
    {
        $this->systemStore->getSystem()->willReturn(null);

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            null
        );

        $this->assertEquals([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ], $permissions);
    }

    public function testGetUserPermissionsWithSystemFromSecurityCondition(): void
    {
        $this->systemStore->getSystem()->willReturn('system1');

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'system2')->shouldBeCalled();
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $anonymousRole1 = $this->prophesize(Role::class);
        $anonymousRole1->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $anonymousRole1->getSystem()->willReturn('system1');
        $anonymousRole1->getId()->willReturn(1);

        $permission2 = $this->prophesize(Permission::class);
        $permission2->getPermissions()->willReturn(32);
        $permission2->getContext()->willReturn('example');
        $anonymousRole2 = $this->prophesize(Role::class);
        $anonymousRole2->getPermissions()->willReturn([$permission2->reveal()]);
        $anonymousRole2->getSystem()->willReturn('system2');
        $anonymousRole2->getId()->willReturn(2);

        $this->roleRepository->findAllRoles(['anonymous' => true])->willReturn([$anonymousRole1, $anonymousRole2]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1', 'system2'),
            null
        );

        $this->assertEquals([
            'view' => false,
            'edit' => true,
        ], $permissions);
    }

    public function testGetUserPermissionByArrayWithSystem(): void
    {
        $this->systemStore->getSystem()->willReturn('system1');

        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $anonymousRole1 = $this->prophesize(Role::class);
        $anonymousRole1->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $anonymousRole1->getSystem()->willReturn('system1');
        $anonymousRole1->getId()->willReturn(1);

        $permission2 = $this->prophesize(Permission::class);
        $permission2->getPermissions()->willReturn(32);
        $permission2->getContext()->willReturn('example');
        $anonymousRole2 = $this->prophesize(Role::class);
        $anonymousRole2->getPermissions()->willReturn(new ArrayCollection([$permission2->reveal()]));
        $anonymousRole2->getSystem()->willReturn('system2');
        $anonymousRole2->getId()->willReturn(2);

        $this->roleRepository->findAllRoles(['anonymous' => true])->willReturn([$anonymousRole1, $anonymousRole2]);

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            'de',
            'sulu_page.pages',
            [
                1 => [
                    'view' => true,
                    'edit' => true,
                ],
                2 => [
                    'view' => true,
                    'edit' => false,
                ],
            ],
            null,
            'system2'
        );

        $this->assertEquals([
            'view' => true,
            'edit' => false,
        ], $permissions);
    }

    public function testGetUserPermissionByArrayWithSystemFromSystemStore(): void
    {
        $this->systemStore->getSystem()->willReturn('system1');

        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $anonymousRole1 = $this->prophesize(Role::class);
        $anonymousRole1->getPermissions()->willReturn(new ArrayCollection([$permission1->reveal()]));
        $anonymousRole1->getSystem()->willReturn('system1');
        $anonymousRole1->getId()->willReturn(1);

        $permission2 = $this->prophesize(Permission::class);
        $permission2->getPermissions()->willReturn(32);
        $permission2->getContext()->willReturn('example');
        $anonymousRole2 = $this->prophesize(Role::class);
        $anonymousRole2->getPermissions()->willReturn(new ArrayCollection([$permission2->reveal()]));
        $anonymousRole2->getSystem()->willReturn('system2');
        $anonymousRole2->getId()->willReturn(2);

        $this->roleRepository->findAllRoles(['anonymous' => true])->willReturn([$anonymousRole1, $anonymousRole2]);

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            'de',
            'sulu_page.pages',
            [
                1 => [
                    'view' => true,
                    'edit' => true,
                ],
                2 => [
                    'view' => true,
                    'edit' => false,
                ],
            ],
            null
        );

        $this->assertEquals([
            'view' => true,
            'edit' => true,
        ], $permissions);
    }

    public function testGetUserPermissionByArrayWithoutSystem(): void
    {
        $this->systemStore->getSystem()->willReturn(null);

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            'de',
            'sulu_page.pages',
            [],
            null
        );

        $this->assertEquals([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ], $permissions);
    }

    public function testGetUserPermissionsWithoutAnonymousUser(): void
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        $anonymousRole = $this->prophesize(Role::class);
        $anonymousRole->getPermissions()->willReturn([$permission1->reveal()]);
        $anonymousRole->getSystem()->willReturn('Sulu');
        $anonymousRole->getId()->willReturn(1);

        $this->roleRepository->findAllRoles(['anonymous' => true])->willReturn([$anonymousRole]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            'anon.'
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testAddAccessControlProvider(): void
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->assertCount(2, $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders'));
        $this->assertContains(
            $accessControlProvider1->reveal(),
            $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders')
        );
        $this->assertContains(
            $accessControlProvider2->reveal(),
            $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders')
        );
    }

    public static function provideUserPermission()
    {
        return [
            [
                [1 => ['view' => true, 'edit' => false]],
                32,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => false],
                'Sulu',
            ],
            [
                [1 => ['view' => false, 'edit' => false]],
                96,
                ['de', 'en'],
                'de',
                ['view' => false, 'edit' => false],
                'Sulu',
            ],
            [
                [],
                64,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => false],
                'Sulu',
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
                'Sulu',
            ],
            [
                [
                    1 => ['view' => true, 'edit' => true],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
                'Sulu',
            ],
            [
                [
                    1 => ['view' => false, 'edit' => false],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => false, 'edit' => false],
                'Website',
            ],
            [
                [
                    1 => ['view' => true, 'edit' => false],
                    2 => ['view' => false, 'edit' => true],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
                'Sulu',
            ],
            [
                [
                    1 => ['view' => true, 'edit' => false],
                    2 => ['view' => false, 'edit' => true],
                ],
                0,
                ['de', 'en'],
                'fr',
                ['view' => false, 'edit' => false],
                'Sulu',
            ],
            [
                [
                    1 => ['view' => true, 'edit' => true],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                null,
                ['view' => true, 'edit' => true],
                'Website',
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                96,
                ['en'],
                'de',
                ['view' => false, 'edit' => false],
                'Sulu',
            ],
        ];
    }
}
