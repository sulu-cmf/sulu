<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Authorization\SecurityContextVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SecurityContextVoterTest extends TestCase
{
    use ProphecyTrait;

    protected $permissions = [
        'view' => 64,
        'add' => 32,
        'edit' => 16,
        'delete' => 8,
        'archive' => 4,
        'live' => 2,
        'security' => 1,
    ];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserRole
     */
    protected $userRole;

    /**
     * @var Role
     */
    protected $role;

    /**
     * @var Permission
     */
    protected $permission;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    protected $token;

    /**
     * @var SecurityContextVoter
     */
    protected $voter;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    protected $accessControlManager;

    public function setUp(): void
    {
        $roleIdReflection = new \ReflectionProperty(Role::class, 'id');
        $roleIdReflection->setAccessible(true);

        $this->user = new User();
        $this->userRole = new UserRole();
        $this->role = new Role();
        $roleIdReflection->setValue($this->role, 1);
        $this->role->setName('role1');
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.roles');
        $this->role->addPermission($this->permission);
        $this->userRole->setRole($this->role);
        $this->user->addUserRole($this->userRole);

        $this->token = $this->prophesize(TokenInterface::class);
        $this->token->getUser()->willReturn($this->user);

        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);

        $this->voter = new SecurityContextVoter($this->accessControlManager->reveal(), $this->permissions);
    }

    public function testPositiveVote(): void
    {
        $securityCondition = new SecurityCondition('sulu.security.roles');

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'edit' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNoUserPermissions(): void
    {
        $securityCondition = new SecurityCondition('sulu.security.roles');

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testNegativeVote(): void
    {
        $securityCondition = new SecurityCondition('sulu.security.roles');

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'edit' => false,
            'security' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveVoteWithMultipleAttributes(): void
    {
        $securityCondition = new SecurityCondition('sulu.security.roles', null);

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'add' => true,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view', 'add']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVoteWithMultipleAttributes(): void
    {
        $securityCondition = new SecurityCondition('sulu.security.roles', null);

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'add' => true,
            'security' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view', 'security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }
}
