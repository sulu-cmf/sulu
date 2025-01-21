<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\User;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\SecurityBundle\User\UserProvider;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;

class UserProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<SystemStoreInterface>
     */
    private $systemStore;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->systemStore = $this->prophesize(SystemStoreInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->userProvider = new UserProvider($this->userRepository->reveal(), $this->systemStore->reveal(), $this->entityManager->reveal());

        $this->user = new User();
        $this->user->setUsername('sulu');
        $this->user->setEmail('test@sulu.io');

        $this->userRepository->findUserWithSecurityById($this->user->getId())->willReturn($this->user);
        $this->userRepository->findUserByIdentifier('sulu')->willReturn($this->user);
    }

    public function testLoginFailDisabledUser(): void
    {
        $this->expectException(DisabledException::class);
        $this->user->setEnabled(false);
        $this->userProvider->loadUserByIdentifier('sulu');
    }

    public function testLoginFailLockedUser(): void
    {
        $this->expectException(LockedException::class);
        $this->user->setLocked(true);
        $this->userProvider->loadUserByIdentifier('sulu');
    }

    public function testLoadUserByUsername(): void
    {
        $role = new Role();
        $role->setSystem('Sulu');
        $userRole = new UserRole();
        $userRole->setRole($role);
        $this->user->addUserRole($userRole);
        $this->systemStore->getSystem()
            ->willReturn('Sulu')
            ->shouldBeCalled();
        $user = $this->userProvider->loadUserByIdentifier('sulu');

        $this->assertEquals('test@sulu.io', $user->getEmail());
    }

    public function testRefreshUser(): void
    {
        $this->assertEquals($this->user->getUserIdentifier(), $this->userProvider->refreshUser($this->user)->getUserIdentifier());
    }

    public function testRefreshUserWithLockedUser(): void
    {
        $this->expectException(LockedException::class);
        $this->user->setLocked(true);
        $this->userProvider->refreshUser($this->user);
    }

    public function testRefreshUserWithDisabledUser(): void
    {
        $this->expectException(DisabledException::class);
        $this->user->setEnabled(false);
        $this->userProvider->refreshUser($this->user);
    }

    public function testUpgradePassword(): void
    {
        $this->userProvider->upgradePassword($this->user, 'hashedPass');
        $this->assertEquals('hashedPass', $this->user->getPassword());
    }
}
