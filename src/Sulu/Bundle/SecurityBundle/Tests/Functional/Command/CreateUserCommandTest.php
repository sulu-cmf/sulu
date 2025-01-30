<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Command;

use Sulu\Bundle\SecurityBundle\Command\CreateUserCommand;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var CreateUserCommand
     */
    private $command;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $application = new Application($this->getContainer()->get('kernel'));

        $this->command = new CreateUserCommand(
            $this->getContainer()->get('doctrine.orm.entity_manager'),
            $this->getContainer()->get('sulu.repository.user'),
            $this->getContainer()->get('sulu.repository.role'),
            $this->getContainer()->get('sulu.repository.contact'),
            $this->getContainer()->get('sulu.core.localization_manager'),
            $this->getContainer()->get('sulu_security.salt_generator'),
            $this->getContainer()->get('security.password_hasher_factory'),
            $this->getContainer()->getParameter('sulu_core.locales')
        );
        $this->command->setApplication($application);
        $this->tester = new CommandTester($this->command);
    }

    public function testCreateUser(): void
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test');
        $this->assertEquals('Created user "sulu" in role "test"' . \PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateUserAlreadyExists(): void
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test');
        $this->createUser('sulu', 'test');

        $this->assertEquals('User "sulu" already exists', \trim($this->tester->getDisplay()));
    }

    public function testCreateUserNonExistingRole(): void
    {
        $this->createRole('test');
        $this->createUser('sulu', 'testfoobar');
        $this->assertEquals('Role "testfoobar" not found. The following roles are available: "test"' . \PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateUserNonExistingLocale(): void
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test', 'ax');
        $this->assertStringContainsString('Given locale "ax" is invalid, must be one of "', $this->tester->getDisplay());
    }

    public function testCreateUserNoRoles(): void
    {
        $this->expectExceptionMessage('The system currently has no roles. Use the "sulu:security:role:create" command to create roles.');
        $this->expectException(\RuntimeException::class);
        $this->createUser('sulu', 'blah');
    }

    private function createRole($roleName): void
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $now = new \DateTime();

        $role = new Role();
        $role->setName($roleName);
        $role->setSystem('Sulu');

        $em->persist($role);
        $em->flush();
    }

    private function createUser($username, $role, $locale = 'en'): void
    {
        $this->tester->execute(
            [
                'username' => 'sulu',
                'firstName' => 'Sulu',
                'lastName' => 'Hikaru',
                'email' => 'sulu.hikaru@startrek.com',
                'locale' => $locale,
                'role' => $role,
                'password' => 'sulu',
            ],
            ['interactive' => false]
        );
    }
}
