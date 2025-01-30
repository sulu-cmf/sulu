<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\UserManager;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\SecurityBundle\Entity\GroupRepository;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $eventCollector;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    private $objectManager;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<GroupRepository>
     */
    private $groupRepository;

    /**
     * @var ObjectProphecy<SaltGenerator>
     */
    private $saltGenerator;

    /**
     * @var ObjectProphecy<ContactManager>
     */
    private $contactManager;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $passwordHasherFactory = $this->prophesize(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->eventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->groupRepository = $this->prophesize(GroupRepository::class);
        $this->contactManager = $this->prophesize(ContactManager::class);
        $this->saltGenerator = $this->prophesize(SaltGenerator::class);

        $this->userManager = new UserManager(
            $this->objectManager->reveal(),
            $passwordHasherFactory->reveal(),
            $this->roleRepository->reveal(),
            $this->groupRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $this->userRepository->reveal(),
            $this->eventCollector->reveal(),
            null
        );
    }

    public function testGetFullNameByUserIdForNonExistingUser(): void
    {
        $this->assertNull($this->userManager->getFullNameByUserId(0));
    }

    public function testValidatePasswordNoPattern(): void
    {
        $this->assertTrue($this->userManager->isValidPassword('test 123'));
        $this->assertFalse($this->userManager->isValidPassword(''));
    }

    public function testValidatePasswordWithPattern(): void
    {
        $userManager = new UserManager(
            $this->objectManager->reveal(),
            null,
            $this->roleRepository->reveal(),
            $this->groupRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $this->userRepository->reveal(),
            $this->eventCollector->reveal(),
            '.{8,}'
        );

        $this->assertTrue($userManager->isValidPassword('testtest'));
        $this->assertFalse($userManager->isValidPassword('test'));
    }
}
