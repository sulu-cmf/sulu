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

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;

class DoctrineAccessControlProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var DoctrineAccessControlProvider
     */
    private $doctrineAccessControlProvider;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    private $objectManager;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<AccessControlRepositoryInterface>
     */
    private $accessControlRepository;

    /**
     * @var ObjectProphecy<MaskConverterInterface>
     */
    private $maskConverter;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->accessControlRepository = $this->prophesize(AccessControlRepositoryInterface::class);
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);

        $this->doctrineAccessControlProvider = new DoctrineAccessControlProvider(
            $this->objectManager->reveal(),
            $this->roleRepository->reveal(),
            $this->accessControlRepository->reveal(),
            $this->maskConverter->reveal()
        );
    }

    public function testSetPermissions(): void
    {
        $role1 = $this->prophesize(Role::class);
        $role2 = $this->prophesize(Role::class);
        $this->roleRepository->findRoleById(1)->willReturn($role1->reveal());
        $this->roleRepository->findRoleById(2)->willReturn($role2->reveal());

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);
        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => true])->willReturn(96);

        $accessControl1 = new AccessControl();
        $accessControl1->setEntityClass('AcmeBundle\Example');
        $accessControl1->setEntityId('1');
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1->reveal());

        $accessControl2 = new AccessControl();
        $accessControl2->setEntityClass('AcmeBundle\Example');
        $accessControl2->setEntityId('1');
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2->reveal());

        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)
            ->willReturn([]);

        $this->objectManager->persist($accessControl1)->shouldBeCalled();
        $this->objectManager->persist($accessControl2)->shouldBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testSetPermissionsWithRemovedRoles(): void
    {
        $role1 = $this->prophesize(Role::class);
        $role1->getId()->willReturn(1);
        $role2 = $this->prophesize(Role::class);
        $role2->getId()->willReturn(2);
        $this->roleRepository->findRoleById(1)->willReturn($role1->reveal());
        $this->roleRepository->findRoleById(2)->willReturn($role2->reveal());

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);
        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => true])->willReturn(96);

        $accessControl1 = new AccessControl();
        $accessControl1->setEntityClass('AcmeBundle\Example');
        $accessControl1->setEntityId('1');
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1->reveal());

        $accessControl2 = new AccessControl();
        $accessControl2->setEntityClass('AcmeBundle\Example');
        $accessControl2->setEntityId('1');
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2->reveal());

        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)
            ->willReturn([$accessControl1, $accessControl2]);

        $this->objectManager->remove($accessControl1)->shouldNotBeCalled();
        $this->objectManager->remove($accessControl2)->shouldBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
            ]
        );
    }

    /**
     * Test to ensure https://github.com/sulu/sulu/issues/6095 is fixed.
     */
    public function testSetPermissionsIgnorePermissionOfRemovedRoles(): void
    {
        $role1 = $this->prophesize(Role::class);
        $role1->getId()->willReturn(1);
        $this->roleRepository->findRoleById(1)->willReturn($role1->reveal());
        $this->roleRepository->findRoleById(2)->willReturn(null);

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);
        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => true])->willReturn(96);

        $accessControl1 = new AccessControl();
        $accessControl1->setEntityClass('AcmeBundle\Example');
        $accessControl1->setEntityId('1');
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1->reveal());

        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)
            ->willReturn([]);

        $this->objectManager->persist($accessControl1)->shouldBeCalled();
        $this->objectManager->persist(Argument::any())->shouldBeCalledTimes(1);
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testSetPermissionsWithExistingAccessControl(): void
    {
        $role1 = $this->prophesize(Role::class);
        $role1->getId()->willReturn(1);
        $this->roleRepository->findRoleById(1)->willReturn($role1->reveal());

        $role2 = $this->prophesize(Role::class);
        $role2->getId()->willReturn(2);
        $this->roleRepository->findRoleById(2)->willReturn($role2->reveal());

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);

        $accessControl1 = $this->prophesize(AccessControl::class);
        $accessControl1->getRole()->willReturn($role1);
        $accessControl1->setPermissions(64)->shouldBeCalled();

        $accessControl2 = $this->prophesize(AccessControl::class);
        $accessControl2->getRole()->willReturn($role2);
        $accessControl2->setPermissions(64)->shouldBeCalled();

        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)
            ->willReturn([$accessControl1, $accessControl2]);

        $this->objectManager->persist(Argument::any())->shouldNotBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => false],
            ]
        );
    }

    public function testGetPermissions(): void
    {
        $roleIdReflection = new \ReflectionProperty(Role::class, 'id');
        $roleIdReflection->setAccessible(true);

        $role1 = new Role();
        $roleIdReflection->setValue($role1, 1);

        $role2 = new Role();
        $roleIdReflection->setValue($role2, 2);

        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(96)->willReturn(['view' => true, 'edit' => true]);

        $accessControl1 = new AccessControl();
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1);

        $accessControl2 = new AccessControl();
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2);

        $accessControls = [
            $accessControl1,
            $accessControl2,
        ];
        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1, null)->willReturn($accessControls);

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1),
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testGetPermissionsWithSystem(): void
    {
        $roleIdReflection = new \ReflectionProperty(Role::class, 'id');
        $roleIdReflection->setAccessible(true);

        $role1 = new Role();
        $roleIdReflection->setValue($role1, 1);

        $role2 = new Role();
        $roleIdReflection->setValue($role2, 2);

        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(96)->willReturn(['view' => true, 'edit' => true]);

        $accessControl1 = new AccessControl();
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1);

        $accessControl2 = new AccessControl();
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2);

        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1, 'Sulu')->willReturn([$accessControl1]);
        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1, 'Website')
            ->willReturn([$accessControl2]);

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1, 'Sulu'),
            [
                1 => ['view' => true, 'edit' => false],
            ]
        );

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1, 'Website'),
            [
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testGetPermissionsForNotExistingAccessControl(): void
    {
        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1, null)->willReturn([]);

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1),
            []
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSupport')]
    public function testSupport(string $type, bool $supported): void
    {
        $this->assertSame($supported, $this->doctrineAccessControlProvider->supports($type));
    }

    public static function provideSupport()
    {
        return [
            [\stdClass::class, false],
            [SecuredEntityInterface::class, true],
        ];
    }
}
