<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Tests\Functional\UserInterface\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin\TrashAdmin;
use Sulu\Bundle\TrashBundle\Tests\Functional\Traits\CreateTrashItemTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TrashItemControllerTest extends SuluTestCase
{
    use CreateTrashItemTrait;

    public const GRANTED_CONTEXT = 'sulu.context.granted';
    public const NOT_GRANTED_CONTEXT = 'sulu.context.not_granted';

    public const ALT_USER_USERNAME = 'test_alt';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TrashItemRepositoryInterface
     */
    private $repository;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->client = $client;

        static::purgeDatabase();

        $this->entityManager = static::getEntityManager();
        $this->repository = static::getTrashItemRepository();
    }

    public function testCgetAction(): void
    {
        self::setUpUserRole();

        static::createTrashItem(
            'pages',
            'resource-id-1',
            'unlocalized title',
            ['key1' => 'value1', 'key2' => 'value2']
        );

        static::createTrashItem(
            'pages',
            'resource-id-2',
            ['de' => 'german title', 'en' => 'english title', 'fr' => 'french title'],
            ['key1' => 'value1', 'key2' => 'value2'],
            'translation',
            ['locale' => 'en']
        );

        $this->client->jsonRequest('GET', '/api/trash-items', ['locale' => 'de']);
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(2, $content['_embedded']['trash_items']);
        self::assertSame('unlocalized title', $content['_embedded']['trash_items'][0]['resourceTitle']);
        self::assertSame('german title', $content['_embedded']['trash_items'][1]['resourceTitle']);
        self::assertSame('Page', $content['_embedded']['trash_items'][0]['resourceType']);
        self::assertSame('Page (Translation)', $content['_embedded']['trash_items'][1]['resourceType']);

        $this->client->jsonRequest('GET', '/api/trash-items', ['locale' => 'en']);
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(2, $content['_embedded']['trash_items']);
        self::assertSame('unlocalized title', $content['_embedded']['trash_items'][0]['resourceTitle']);
        self::assertSame('english title', $content['_embedded']['trash_items'][1]['resourceTitle']);
        self::assertSame('Page', $content['_embedded']['trash_items'][0]['resourceType']);
        self::assertSame('Page (Translation)', $content['_embedded']['trash_items'][1]['resourceType']);
    }

    public function testCgetActionWithSecurity(): void
    {
        $accessControlManager = static::getContainer()->get('sulu_security.access_control_manager');

        $role = self::setUpUserRole();

        $count = 0;
        foreach ([null, self::GRANTED_CONTEXT, self::NOT_GRANTED_CONTEXT] as $resourceSecurityContext) {
            foreach ([null, true, false] as $objectSecurity) {
                $resourceId = (string) ++$count;
                $resourceSecurityObjectType = null !== $objectSecurity ? SecuredEntityInterface::class : null;
                $resourceSecurityObjectId = null !== $objectSecurity ? $resourceId : null;

                static::createTrashItem(
                    'test_resource',
                    $resourceId,
                    'Resource title',
                    [],
                    null,
                    [],
                    $resourceSecurityContext,
                    $resourceSecurityObjectType,
                    $resourceSecurityObjectId
                );

                if (null !== $resourceSecurityObjectType && null !== $resourceSecurityObjectId) {
                    $accessControlManager->setPermissions(
                        $resourceSecurityObjectType,
                        $resourceSecurityObjectId,
                        [
                            $role->getId() => [
                                PermissionTypes::VIEW => $objectSecurity,
                                PermissionTypes::DELETE => $objectSecurity,
                            ],
                        ]
                    );
                }
            }
        }

        $this->client->jsonRequest('GET', '/api/trash-items', ['limit' => 0]);
        $content = \json_decode((string) $this->client->getResponse()->getContent());

        self::assertSame(4, $content->total);
    }

    public function testGetAction(): void
    {
        $trashItem = static::createTrashItem();
        $id = $trashItem->getId();

        $this->client->jsonRequest('GET', '/api/trash-items/' . $id);
        static::assertHttpStatusCode(200, $this->client->getResponse());
        $content = \json_decode((string) $this->client->getResponse()->getContent());

        static::assertSame($id, $content->id);
    }

    public function testGetActionWithPermissionCheck(): void
    {
        self::setUpUserRole();
        // Needed to rename the username to bypass TestVoter.
        self::renameUserUsername(self::ALT_USER_USERNAME);

        $trashItem = static::createTrashItem();
        $id = $trashItem->getId();

        $this->client->jsonRequest('GET', '/api/trash-items/' . $id, [], [
            'PHP_AUTH_USER' => self::ALT_USER_USERNAME,
            'PHP_AUTH_PW' => 'test',
        ]);
        static::assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testDeleteAction(): void
    {
        $trashItem = static::createTrashItem();
        $id = $trashItem->getId();

        $this->client->jsonRequest('DELETE', '/api/trash-items/' . $id);
        static::assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/trash-items/' . $id);
        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteActionNotExisting(): void
    {
        $this->client->jsonRequest('DELETE', '/api/trash-items/12345');
        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPostTriggerActionRestore(): void
    {
        $trashItem = static::createTrashItem();
        $id = $trashItem->getId();

        $this->client->jsonRequest('POST', '/api/trash-items/' . $id, ['action' => 'restore']);
        static::assertHttpStatusCode(200, $this->client->getResponse());

        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('property1', $content);
        static::assertSame('value-1', $content['property1']);

        // only properties with "restoreSerializationGroup" should be included
        static::assertArrayNotHasKey('property2', $content);

        $this->client->jsonRequest('GET', '/api/trash-items/' . $id);
        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPostTriggerActionRestoreWithPermissionCheck(): void
    {
        self::setUpUserRole();
        // Needed to rename the username to bypass TestVoter.
        self::renameUserUsername(self::ALT_USER_USERNAME);

        $trashItem = static::createTrashItem();
        $id = $trashItem->getId();

        $this->client->jsonRequest('POST', '/api/trash-items/' . $id, ['action' => 'restore'], [
            'PHP_AUTH_USER' => self::ALT_USER_USERNAME,
            'PHP_AUTH_PW' => 'test',
        ]);
        static::assertHttpStatusCode(200, $this->client->getResponse());
    }

    private static function setUpUserRole(): Role
    {
        $entityManager = static::getEntityManager();
        $testUser = static::getTestUser();

        $role = new Role();
        $role->setAnonymous(false);
        $role->setKey('test');
        $role->setName('Test');
        $role->setSystem(Admin::SULU_ADMIN_SECURITY_SYSTEM);

        $entityManager->persist($role);

        $grantedPermission = new Permission();
        $grantedPermission->setContext(static::GRANTED_CONTEXT);
        $grantedPermission->setPermissions(127);
        $grantedPermission->setRole($role);

        $entityManager->persist($grantedPermission);

        $role->addPermission($grantedPermission);

        $notGrantedPermission = new Permission();
        $notGrantedPermission->setContext(static::NOT_GRANTED_CONTEXT);
        $notGrantedPermission->setPermissions(0);
        $notGrantedPermission->setRole($role);

        $entityManager->persist($notGrantedPermission);

        $role->addPermission($notGrantedPermission);

        $trashPermission = new Permission();
        $trashPermission->setContext(TrashAdmin::SECURITY_CONTEXT);
        $trashPermission->setPermissions(127);
        $trashPermission->setRole($role);

        $entityManager->persist($trashPermission);

        $role->addPermission($trashPermission);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setLocale('["en"]');
        $userRole->setUser($testUser);
        $entityManager->persist($userRole);

        $testUser->addUserRole($userRole);

        $entityManager->flush();

        return $role;
    }

    private static function renameUserUsername(string $username): void
    {
        $entityManager = static::getEntityManager();
        $user = static::getTestUser();

        $user->setUsername($username);

        $entityManager->flush();
    }

    protected static function getTrashItemRepository(): TrashItemRepositoryInterface
    {
        return static::getContainer()->get('sulu_trash.trash_item_repository');
    }
}
