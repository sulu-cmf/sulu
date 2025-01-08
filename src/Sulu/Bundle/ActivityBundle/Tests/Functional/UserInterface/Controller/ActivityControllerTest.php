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

namespace Sulu\Bundle\ActivityBundle\Tests\Functional\UserInterface\Controller;

use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ActivityControllerTest extends SuluTestCase
{
    public const GRANTED_CONTEXT = 'sulu.context.granted';
    public const NOT_GRANTED_CONTEXT = 'sulu.context.not_granted';

    /**
     * @var KernelBrowser
     */
    private $client;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();

        $accessControlManager = static::getContainer()->get('sulu_security.access_control_manager');

        $role = static::setUpUserRole();

        $count = 0;
        foreach (['pages', 'other'] as $resourceKey) {
            foreach ([null, 'en', 'de'] as $resourceLocale) {
                foreach ([null, self::GRANTED_CONTEXT, self::NOT_GRANTED_CONTEXT] as $resourceSecurityContext) {
                    foreach ([null, true, false] as $objectSecurity) {
                        $resourceId = (string) ++$count;
                        $resourceSecurityObjectType = null !== $objectSecurity ? SecuredEntityInterface::class : null;
                        $resourceSecurityObjectId = null !== $objectSecurity ? $resourceId : null;

                        self::createActivity(
                            $resourceKey,
                            $resourceId,
                            $resourceLocale,
                            $resourceSecurityContext,
                            $resourceSecurityObjectType,
                            $resourceSecurityObjectId
                        );

                        if (null !== $resourceSecurityObjectType && null !== $resourceSecurityObjectId) {
                            $accessControlManager->setPermissions(
                                $resourceSecurityObjectType,
                                $resourceSecurityObjectId,
                                [
                                    $role->getId() => [PermissionTypes::VIEW => $objectSecurity],
                                ]
                            );
                        }
                    }
                }
            }
        }

        static::ensureKernelShutdown();
    }

    public function setUp(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->client = $client;
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function provideCgetAction(): \Generator
    {
        yield [
            [],
            24,
        ];

        yield [
            ['locale' => 'en'],
            16,
        ];

        yield [
            ['resourceKey' => 'pages'],
            12,
        ];

        yield [
            ['resourceId' => 'anything'], // resourceId is only taken into account, if resourceKey is set
            24,
        ];

        yield [
            ['locale' => 'de', 'resourceKey' => 'other'],
            8,
        ];

        yield [
            ['resourceKey' => 'pages', 'resourceId' => 1],
            1,
        ];

        yield [
            ['resourceKey' => 'pages', 'resourceId' => 3], // Not granted object permission
            0,
        ];

        yield [
            ['resourceKey' => 'pages', 'resourceId' => 7], // Not granted context permission
            0,
        ];
    }

    /**
     * @dataProvider provideCgetAction
     *
     * @param mixed[] $parameters
     */
    public function testCgetAction(array $parameters, int $expectedTotal): void
    {
        $urlParameters = \array_merge(
            ['limit' => 0],
            $parameters
        );

        $this->client->jsonRequest('GET', '/api/activities', $urlParameters);
        $content = \json_decode((string) $this->client->getResponse()->getContent());

        self::assertSame($expectedTotal, $content->total);
    }

    public function testCgetActionActivityText(): void
    {
        $this->client->jsonRequest('GET', '/api/activities', [
            'resourceKey' => 'pages',
            'resourceId' => 1,
        ]);

        $content = \json_decode((string) $this->client->getResponse()->getContent());

        self::assertSame(
            '<b>Max Mustermann</b> has created the page "Test Page 1234"',
            $content->_embedded->activities[0]->description
        );

        self::assertSame(
            'Page',
            $content->_embedded->activities[0]->resource
        );
    }

    public function testCgetActionActivityResource(): void
    {
        $this->client->jsonRequest('GET', '/api/activities', [
            'resourceKey' => 'pages',
            'resourceId' => 1,
        ]);

        $content = \json_decode((string) $this->client->getResponse()->getContent());

        self::assertSame(
            'Page',
            $content->_embedded->activities[0]->resource
        );
    }

    private static function createActivity(
        string $resourceKey,
        string $resourceId,
        ?string $resourceLocale,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): Activity {
        $repository = static::getContainer()->get('sulu_activity.activity_repository.doctrine');
        $testUser = static::getTestUser();

        $activity = new Activity();
        $activity->setType('created');
        $activity->setContext(['foo' => 'bar']);
        $activity->setPayload(['bar' => 'baz']);
        $activity->setTimestamp(new \DateTimeImmutable());
        $activity->setBatch('batch-1234');
        $activity->setUser($testUser);
        $activity->setResourceKey($resourceKey);
        $activity->setResourceId($resourceId);
        $activity->setResourceLocale($resourceLocale);
        $activity->setResourceWebspaceKey('sulu-io');
        $activity->setResourceTitle('Test Page 1234');
        $activity->setResourceTitleLocale('en');
        $activity->setResourceSecurityContext($resourceSecurityContext);
        $activity->setResourceSecurityObjectType($resourceSecurityObjectType);
        $activity->setResourceSecurityObjectId($resourceSecurityObjectId);

        $repository->addAndCommit($activity);

        return $activity;
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

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setLocale('["en"]');
        $userRole->setUser($testUser);
        $entityManager->persist($userRole);

        $testUser->addUserRole($userRole);

        $entityManager->flush();

        return $role;
    }
}
