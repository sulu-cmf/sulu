<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Behat;

use Sulu\Bundle\TestBundle\Behat\BaseContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\Permission;

/**
 * Behat context class for the SecurityBundle
 */
class SecurityContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @Given the user :username exists with password :password
     */
    public function theUserExistsWithPassword($username, $password)
    {
        $this->getOrCreateRole('User', 'Sulu');
        $this->execCommand('sulu:security:user:create', array(
            'username' => $username,
            'firstName' => 'Adam',
            'lastName' => 'Ministrator',
            'email' => 'admin@example.com',
            'locale' => 'en',
            'password' => $password,
            'role' => 'User',
        ));
    }

    /**
     * @Given the following users exist:
     */
    public function theFollowingUsersExist(TableNode $users)
    {
        $this->getOrCreateRole('User', 'Sulu');
        $users = $users->getColumnsHash();

        foreach ($users as $user) {
            $this->execCommand('sulu:security:user:create', array(
                'username' => $user['username'],
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'locale' => $user['locale'],
                'password' => $user['password'],
                'role' => 'admin',
            ));
        }
    }

    /**
     * @Given the following roles exist:
     */
    public function theFollowingRolesExist(TableNode $roles)
    {
        $roleData = $roles->getColumnsHash();

        foreach ($roleData as $roleDatum) {
            $this->getOrCreateRole($roleDatum['name'], $roleDatum['system']);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @Then the role :name should not exist
     */
    public function theRoleShouldNotExist($name)
    {
        $role = $this->getEntityManager()
            ->getRepository('SuluSecurityBundle:Role')->findOneBy(array(
                'name' => $name, 
            ));

        if ($role) {
            throw new \Exception(sprintf('Role with name "%s" should NOT exist', $name));
        }
    }

    /**
     * @Given I am logged in as an administrator
     */
    public function iAmLoggedInAsAnAdministrator()
    {
        $this->theUserExistsWithPassword('admin', 'admin');
        $this->visitPath('/admin');
        $page = $this->getSession()->getPage();
        $this->waitForSelector('#username');
        $this->fillSelector('#username', 'admin');
        $this->fillSelector('#password', 'admin');
        $loginButton = $page->findById('login-button');

        if (!$loginButton) {
            throw new \InvalidArgumentException(
                'Could not find submit button on login page'
            );
        }
        
        $loginButton->click();
        $this->getSession()->wait(5000, "document.querySelector('.navigation')");
    }

    private function getOrCreateRole($name, $system)
    {
        $role = $this->getEntityManager()
            ->getRepository('Sulu\Bundle\SecurityBundle\Entity\Role')
            ->findOneByName($name);

        if ($role) {
            return $role;
        }

        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);
        $pool = $this->getContainer()->get('sulu_admin.admin_pool');
        $securityContexts = $pool->getSecurityContexts();

        $securityContextsFlat = array();
        array_walk_recursive(
            $securityContexts['Sulu'],
            function ($value) use (&$securityContextsFlat) {
                $securityContextsFlat[] = $value;
            }
        );

        foreach ($securityContextsFlat as $securityContext) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setContext($securityContext);
            $permission->setPermissions(120);
            $role->addPermission($permission);
        }

        $this->getEntityManager()->persist($role);
        $this->getEntityManager()->flush();

        return $role;
    }
}
