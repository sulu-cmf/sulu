<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Tests\Application\Kernel;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
class SecurityListenerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        if (Kernel::VERSION_ID < 50000) { // @phpstan-ignore-line
            $this->markTestSkipped('This test is only for Symfony 5.0 and above');
        }

        $this->client = $this->createWebsiteClient(['environment' => 'test_with_security']);
        $this->purgeDatabase();
        $this->initPhpcr();
    }

    public function testNoPermissions(): void
    {
        $pageDocument = $this->createSecuredPage();

        $this->client->request('GET', 'http://sulu.lo/');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('Location'));
    }

    public function testRedirectToLoginWhenNoAccess(): void
    {
        $pageDocument = $this->createSecuredPage();

        $this->client->request('GET', 'http://sulu.lo/secure-area');
        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://sulu.lo/login', $response->headers->get('Location'));
    }

    private function createSecuredPage(): BasePageDocument
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $entityManager = $this->getEntityManager();

        $pageData = [
            'locale' => 'en',
            'title' => 'Secure Area',
            'url' => '/secure-area',
            'article' => '<p>Some sample text for this super secret area.</p>',
            'structureType' => 'default',
        ];

        $extensionData = [
            'seo' => [],
            'excerpt' => [],
        ];

        /** @var PageDocument $pageDocument */
        $pageDocument = $documentManager->create('page');

        $pageDocument->setNavigationContexts([]);
        $pageDocument->setLocale($pageData['locale']);
        $pageDocument->setTitle($pageData['title']);
        $pageDocument->setResourceSegment($pageData['url']);
        $pageDocument->setStructureType($pageData['structureType']);
        $pageDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $pageDocument->getStructure()->bind($pageData);
        $pageDocument->setAuthor(1);
        $pageDocument->setExtensionsData($extensionData);
        $pageDocument->setPermissions([
            1 => [ // do not allow anonymous users to access this page
                'view' => false,
            ],
        ]);

        $documentManager->persist(
            $pageDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/contents']
        );
        $documentManager->flush();

        // We need to add access control here as we do not have the document id before
        $role = new Role();
        $role->setName('Anonymous User Website');
        $role->setSystem('sulu_io');
        $role->setAnonymous(true);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(127);
        $permission->setContext('sulu.webspaces.sulu_io');
        $role->addPermission($permission);

        $accessControl = new AccessControl();
        $accessControl->setPermissions(0);
        $accessControl->setEntityId($pageDocument->getUuid());
        $accessControl->setEntityClass(SecurityBehavior::class);
        $accessControl->setRole($role);

        $entityManager->persist($permission);
        $entityManager->persist($role);
        $entityManager->persist($accessControl);
        $entityManager->flush();

        $pageDocument->setPermissions([
            $role->getId() => [ // do not allow anonymous users to access this page
                'view' => false,
            ],
        ]);
        $documentManager->persist(
            $pageDocument,
            'en',
            ['parent_path' => '/cmf/sulu_io/contents']
        );
        $documentManager->flush();
        $documentManager->publish($pageDocument, 'en');

        $documentManager->clear();
        $entityManager->clear();

        return $pageDocument;
    }
}
