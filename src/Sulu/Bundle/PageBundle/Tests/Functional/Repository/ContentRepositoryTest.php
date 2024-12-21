<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\SessionInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepository;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ContentRepositoryTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    public function setUp(): void
    {
        $this->purgeDatabase();

        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->propertyEncoder = $this->getContainer()->get('sulu_document_manager_test.property_encoder');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->localizationFinder = $this->getContainer()->get('sulu.content.localization_finder');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->nodeHelper = $this->getContainer()->get('sulu.util.node_helper');
        $this->systemStore = $this->getContainer()->get('sulu_security.system_store');

        $this->contentRepository = new ContentRepository(
            $this->sessionManager,
            $this->propertyEncoder,
            $this->webspaceManager,
            $this->localizationFinder,
            $this->structureManager,
            $this->nodeHelper,
            $this->systemStore,
            ['view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8]
        );

        $this->initPhpcr();

        $this->homeDocument = $this->documentManager->find($this->sessionManager->getContentPath('sulu_io'), 'de');
    }

    public function testFindByParent(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());

        $this->assertEquals(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => true],
                $role2->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => true,
                ],
                $role3->getId() => [
                    'view' => false,
                    'add' => true,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $result[0]->getPermissions()
        );
        $this->assertEquals([], $result[1]->getPermissions());
        $this->assertEquals([], $result[2]->getPermissions());
    }

    public function testFindDescendantIdsById(): void
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de', [], $page1);
        $page3 = $this->createPage('test-3', 'de', [], $page1);

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findDescendantIdsById($parentUuid);

        $this->assertCount(3, $result);

        $this->assertContains($page1->getUuid(), $result);
        $this->assertContains($page2->getUuid(), $result);
        $this->assertContains($page3->getUuid(), $result);
    }

    public function testFindByParentMapping(): void
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithShadow(): void
    {
        $this->createShadowPage('test-1', 'de', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithShadowNoHydrate(): void
    {
        $this->createShadowPage('test-1', 'en_us', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->setHydrateShadow(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(2, $result);

        $this->assertEquals('test-2', $result[0]['title']);
        $this->assertEquals('test-3', $result[1]['title']);
    }

    public function testFindByParentWithGhost(): void
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('de', $result[0]->getLocale());
        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('ghost', $result[0]->getLocalizationType()->getName());
        $this->assertEquals('en', $result[0]->getLocalizationType()->getValue());
        $this->assertEquals('de', $result[1]->getLocale());
        $this->assertNull($result[1]->getLocalizationType());
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('de', $result[2]->getLocale());
        $this->assertNull($result[2]->getLocalizationType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithGhostNoHydrate(): void
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->setHydrateGhost(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(2, $result);

        $this->assertEquals('test-2', $result[0]['title']);
        $this->assertEquals('test-3', $result[1]['title']);
    }

    public function testFindByParentWithInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithDraftInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link, false);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title', 'published'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEmpty($result[1]['published']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLinkNotFollow(): void
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->setFollowInternalLink(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLinkAndShadow(): void
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $this->createInternalLinkPage('test-2', 'en', $link);
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentOneLayer(): void
    {
        $page1 = $this->createPage('test-1', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-2', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-2-1', 'de', [], $page2);
        $this->createPage('test-2-2', 'de', [], $page2);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByWebspaceRoot(): void
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByWebspaceRootWithPermissions(): void
    {
        $role1 = $this->createRole('Role 1', 'Website');
        $role2 = $this->createRole('Role 2', 'Website');
        $role3 = $this->createRole('Role 3', 'Sulu');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Website');

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertCount(3, $result);

        $this->assertEquals(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => true],
                $role2->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => true,
                ],
                $role3->getId() => [
                    'view' => false,
                    'add' => true,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $result[0]->getPermissions()
        );
        $this->assertEquals([], $result[1]->getPermissions());
        $this->assertEquals([], $result[2]->getPermissions());
    }

    public function testFindByWebspaceRootNonExistingLocale(): void
    {
        $this->createPage('test-1', 'de');

        $result = $this->contentRepository->findByWebspaceRoot('fr', 'sulu_io', MappingBuilder::create()->getMapping());

        $this->assertCount(1, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertEquals('ghost', $result[0]->getLocalizationType()->getName());
        $this->assertEquals('de', $result[0]->getLocalizationType()->getValue());
        $this->assertEquals('fr', $result[0]->getLocale());
    }

    public function testFindByWebspaceRootMapping(): void
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithShadow(): void
    {
        $this->createShadowPage('test-1', 'de', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $result = $this->contentRepository->findByWebspaceRoot(
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithInternalLinkAndShadow(): void
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $this->createInternalLinkPage('test-2', 'en', $link);
        $this->createPage('test-3', 'en');

        $result = $this->contentRepository->findByWebspaceRoot(
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootOneLayer(): void
    {
        $page1 = $this->createPage('test-1', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-2', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-2-1', 'de', [], $page2);
        $this->createPage('test-2-2', 'de', [], $page2);
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot('de', 'sulu_io', MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFind(): void
    {
        $page = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-1', $result->getPath());
        $this->assertEquals('simple', $result->getTemplate());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithGhost(): void
    {
        $page = $this->createPage('test-1', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'en_us',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-1', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithShadow(): void
    {
        $page = $this->createShadowPage('test-1', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/1-tset', $result->getPath()); // path will be generated with reversed string
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->setResolveUrl(true)->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('/test-1', $result->getUrl());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindWithUnpublishedInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de', [], null, [], false);
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        // should load content with requested node and not try to follow internal link

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);

        static::bootKernel([
            'sulu.context' => SuluKernel::CONTEXT_WEBSITE,
        ]);
        $this->setUp();

        $this->expectException(ItemNotFoundException::class);
        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );
    }

    public function testFindWithEmptyInternalLink(): void
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $node = $this->session->getNodeByIdentifier($page->getUuid());
        $node->getProperty('i18n:de-internal_link')->remove();
        $this->session->save();

        // should load content with requested node and not try to follow internal link

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);

        static::bootKernel([
            'sulu.context' => SuluKernel::CONTEXT_WEBSITE,
        ]);
        $this->setUp();

        $this->expectException(ItemNotFoundException::class);
        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );
    }

    public function testFindWithInternalLinkToItself(): void
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $node = $this->session->getNodeByIdentifier($page->getUuid());
        $node->setProperty('i18n:de-internal_link', $node);
        $this->session->save();

        // should load content with requested node and not try to follow internal link

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindWithInternalLinkAndShadow(): void
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindWithNonFallbackProperties(): void
    {
        $link = $this->createPage('test-1', 'de');
        \sleep(1); // create a difference between link and page (created / changed)
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(
                [
                    'title',
                    'created',
                    'changed',
                ]
            )->getMapping()
        );

        $created = $result['created'];
        $changed = $result['changed'];

        // Jackalope Jackrabbit will return a \DateTime and DBAL will return a
        // string. See: https://github.com/jackalope/jackalope-doctrine-dbal/issues/325
        if (\is_string($created)) {
            $created = new \DateTime($result['created']);
        }

        if (\is_string($changed)) {
            $changed = new \DateTime($result['changed']);
        }

        $this->assertGreaterThan($link->getCreated(), $created);
        $this->assertGreaterThan($link->getChanged(), $changed);

        // Reload the document.
        //
        // Jackalope Doctrine DBAL will currently "normalize" to UTC when
        // persisting and use the system TZ when loading, however there is/was
        // an actual bug whereby the state of the objects DateTime object was
        // changed such that it was incorrect in the loaded entity after
        // persisting.
        //
        // Reloading the document is a workaround to ensure we have the correct
        // value.
        //
        // PR: https://github.com/jackalope/jackalope-doctrine-dbal/pull/326 has
        // already been merged for this, and we can remove this after upgrading
        // to the next jackalope DBAL release.
        $page = $this->documentManager->find($page->getUuid(), 'de');

        $this->assertEquals($page->getCreated()->format('c'), $created->format('c'));
        $this->assertEquals($page->getChanged()->format('c'), $changed->format('c'));

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindPermissions(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertEquals(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => true],
                $role2->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => true,
                ],
                $role3->getId() => [
                    'view' => false,
                    'add' => true,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $result->getPermissions()
        );
    }

    public function testFindWithoutPermissions(): void
    {
        $role1 = $this->prophesize(RoleInterface::class);
        $role1->getId()->willReturn(1);
        $role1->getIdentifier()->willReturn('ROLE_SULU_ROLE 1');
        $role2 = $this->prophesize(RoleInterface::class);
        $role2->getId()->willReturn(2);
        $role2->getIdentifier()->willReturn('ROLE_SULU_ROLE-2');

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1->reveal(), $role2->reveal()]);

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null
        );

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertEquals(
            [],
            $result->getPermissions()
        );
    }

    public function testFindWithPermissionsNotGranted(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => false],
                $role2->getId() => ['view' => false, 'archive' => false],
                $role3->getId() => ['add' => false],
            ]
        );

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertEquals(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => false],
                $role2->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false],
                $role3->getId() => [
                    'view' => false,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $result->getPermissions()
        );
    }

    public static function provideWebspaceKeys()
    {
        return [['sulu_io'], ['test_io']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideWebspaceKeys')]
    public function testFindParentsWithSiblingsByUuid($webspaceKey): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page1 = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de', [], $page1);
        $page4 = $this->createPage('test-4', 'de', [], $page1);
        $page5 = $this->createPage('test-5', 'de', [], $page2);
        $page6 = $this->createPage('test-6', 'de', [], $page2);
        $page7 = $this->createPage('test-7', 'de', [], $page3);
        $page8 = $this->createPage('test-8', 'de', [], $page4);
        $page9 = $this->createPage('test-9', 'de', [], $page6);
        $page10 = $this->createPage('test-10', 'de', [], $page6);
        $page11 = $this->createPage('test-11', 'de', [], $page10);
        $page12 = $this->createPage('test-12', 'de', [], $page10);
        $page13 = $this->createPage('test-13', 'de', [], $page12);

        $result = $this->contentRepository->findParentsWithSiblingsByUuid(
            $page10->getUuid(),
            'de',
            $webspaceKey,
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $layer = $result;
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]->getId());
        $this->assertEquals(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => true],
                $role2->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => true,
                ],
                $role3->getId() => [
                    'view' => false,
                    'add' => true,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $layer[0]->getPermissions()
        );
        $this->assertTrue($layer[0]->hasChildren());
        $this->assertNull($layer[0]->getChildren());
        $this->assertEquals($page2->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertEquals([], $layer[1]->getPermissions());
        $this->assertCount(2, $layer[1]->getChildren());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page5->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertNull($layer[0]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());
        $this->assertEquals($page6->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(2, $layer[1]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page9->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertNull($layer[0]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());
        $this->assertEquals($page10->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(2, $layer[1]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page11->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertNull($layer[0]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());
        $this->assertEquals($page12->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertNull($layer[1]->getChildren());
        $this->assertEquals([], $layer[1]->getPermissions());
    }

    public function testFindParentsWithSiblingsByUuidWithoutWebspaceKey(): void
    {
        $page = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->findParentsWithSiblingsByUuid(
            $page->getUuid(),
            'de',
            '',
            MappingBuilder::create()->getMapping()
        );

        $this->assertCount(1, $result);
        $this->assertEquals($page->getUuid(), $result[0]->getId());
        $this->assertEquals($page->getWebspaceName(), $result[0]->getWebspaceKey());
    }

    public function testFindByPaths(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page1 = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $page11 = $this->createPage('test-1/test-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByPaths(
            ['/cmf/sulu_io/contents', '/cmf/sulu_io/contents/test-1', '/cmf/sulu_io/contents/test-2'],
            'de',
            MappingBuilder::create()->addProperties(['title'])->getMapping(),
            $user->reveal()
        );

        $this->assertCount(3, $result);

        $homepageUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();
        $order = [
            $homepageUuid,
            $page1->getUuid(),
            $page2->getUuid(),
        ];
        \usort($result, function(Content $a, Content $b) use ($order) {
            $posA = \array_search($a->getId(), $order);
            $posB = \array_search($b->getId(), $order);

            return $posA - $posB;
        });

        $items = \array_map(
            function(Content $content) {
                return [
                    'uuid' => $content->getId(),
                    'hasChildren' => $content->hasChildren(),
                    'children' => $content->getChildren(),
                    'permissions' => $content->getPermissions(),
                ];
            },
            $result
        );

        $this->assertSame(
            [
                'uuid' => $homepageUuid,
                'hasChildren' => true,
                'children' => null,
                'permissions' => [],
            ],
            $items[0]
        );

        $this->assertSame(
            [
                'uuid' => $page1->getUuid(),
                'hasChildren' => true,
                'children' => null,
                'permissions' => [
                    $role1->getId() => ['view' => false, 'add' => false, 'edit' => true, 'delete' => false],
                    $role2->getId() => [
                        'view' => true,
                        'add' => false,
                        'edit' => false,
                        'delete' => false,
                        'archive' => true,
                    ],
                    $role3->getId() => [
                        'view' => false,
                        'add' => true,
                        'edit' => false,
                        'delete' => false,
                    ],
                ],
            ],
            $items[1]
        );
        $this->assertSame(
            [
                'uuid' => $page2->getUuid(),
                'hasChildren' => false,
                'children' => null,
                'permissions' => [],
            ],
            $items[2]
        );
    }

    public function testFindByUuids(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page1 = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $page11 = $this->createPage('test-1/test-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByUuids(
            [$page1->getUuid(), $page2->getUuid()],
            'de',
            MappingBuilder::create()->addProperties(['title'])->getMapping(),
            $user->reveal()
        );

        $this->assertCount(2, $result);

        $items = \array_map(
            function(Content $content) {
                return [
                    'uuid' => $content->getId(),
                    'hasChildren' => $content->hasChildren(),
                    'children' => $content->getChildren(),
                    'permissions' => $content->getPermissions(),
                ];
            },
            $result
        );

        $this->assertSame(
            [
                'uuid' => $page1->getUuid(),
                'hasChildren' => true,
                'children' => null,
                'permissions' => [
                    $role1->getId() => ['view' => false, 'add' => false, 'edit' => true, 'delete' => false],
                    $role2->getId() => [
                        'view' => true,
                        'add' => false,
                        'edit' => false,
                        'delete' => false,
                        'archive' => true,
                    ],
                    $role3->getId() => [
                        'view' => false,
                        'add' => true,
                        'edit' => false,
                        'delete' => false,
                    ],
                ],
            ],
            $items[0]
        );
        $this->assertSame(
            [
                'uuid' => $page2->getUuid(),
                'hasChildren' => false,
                'children' => null,
                'permissions' => [],
            ],
            $items[1]
        );
    }

    public function testFindAll(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page1 = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );
        $page11 = $this->createPage('test-1-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findAll(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping(),
            $user->reveal()
        );

        $paths = \array_map(
            function(Content $content) {
                return $content->getPath();
            },
            $result
        );

        $this->assertContains('/', $paths);
        $this->assertContains('/test-1', $paths);
        $this->assertContains('/test-1/test-1-1', $paths);
        $this->assertContains('/test-2', $paths);
        $this->assertContains('/test-3', $paths);

        $permissions = \array_map(
            function(Content $content) {
                return $content->getPermissions();
            },
            $result
        );

        $this->assertContains(
            [
                $role1->getId() => ['view' => false, 'add' => false, 'edit' => true, 'delete' => false],
                $role2->getId() => [
                    'view' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => true,
                ],
                $role3->getId() => [
                    'view' => false,
                    'add' => true,
                    'edit' => false,
                    'delete' => false,
                ],
            ],
            $permissions
        );
    }

    public function testFindAllNoPage(): void
    {
        $result = $this->contentRepository->findAll(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(1, $result);

        $paths = \array_map(
            function(Content $content) {
                return $content->getPath();
            },
            $result
        );

        $this->assertContains('/', $paths);
    }

    public function testFindAllByPortal(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Sulu');
        $role3 = $this->createRole('Role 3', 'Website');

        $this->em->flush();

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1, $role2]);

        $this->systemStore->setSystem('Sulu');

        $page1 = $this->createPage(
            'test-1',
            'de_at',
            [],
            null,
            [
                $role1->getId() => ['edit' => true],
                $role2->getId() => ['view' => true, 'archive' => true],
                $role3->getId() => ['add' => true],
            ]
        );

        $result = $this->contentRepository->findAllByPortal(
            'de_at',
            'sulucmf_at',
            MappingBuilder::create()->setResolveUrl(true)->getMapping(),
            $user->reveal()
        );

        \usort($result, function($content1, $content2) {
            return \strcmp($content1->getPath(), $content2->getPath());
        });

        $this->assertCount(2, $result);
        $this->assertEquals([
            $role1->getId() => ['view' => false, 'add' => false, 'delete' => false, 'edit' => true],
            $role2->getId() => [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
                'archive' => true,
            ],
            $role3->getId() => [
                'view' => false,
                'add' => true,
                'edit' => false,
                'delete' => false,
            ],
        ], $result[1]->getPermissions());
        $urls = $result[1]->getUrls();
        $this->assertEquals('/test-1', $urls['de_at']);
        $this->assertNull($urls['de']);
        $this->assertNull($urls['en']);
        $this->assertNull($urls['en_us']);

        $this->assertEquals([], $result[0]->getPermissions());
        $urls = $result[0]->getUrls();
        $this->assertEquals('/', $urls['de_at']);
        $this->assertEquals('/', $urls['de']);
        $this->assertEquals('/', $urls['en']);
        $this->assertEquals('/', $urls['en_us']);
    }

    public function testFindUrl(): void
    {
        $page1 = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->find(
            $page1->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->setResolveUrl(true)->getMapping()
        );

        $this->assertEquals('/test-1', $result->getUrl());
        $this->assertEquals(
            [
                'en' => null,
                'en_us' => null,
                'de' => '/test-1',
                'de_at' => null,
                'fr' => null,
            ],
            $result->getUrls()
        );
    }

    public function testFindUrls(): void
    {
        $page1 = $this->createShadowPage('test-1', 'de', 'en');

        $result = $this->contentRepository->find(
            $page1->getUuid(),
            'de_at',
            'sulu_io',
            MappingBuilder::create()->setResolveUrl(true)->getMapping()
        );

        $this->assertEquals(
            [
                'en' => '/test-1',
                'en_us' => null,
                'de' => '/test-1',
                'de_at' => null,
                'fr' => null,
            ],
            $result->getUrls()
        );
    }

    public function testFindByWebspaceRootPublished(): void
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page2->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist(
            $page2,
            'de',
            [
                'path' => $this->sessionManager->getContentPath('sulu_io') . '/test-2',
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->setOnlyPublished(true)->getMapping()
        );

        $this->assertCount(1, $result);
        $this->assertEquals('/test-1', $result[0]->getPath());
    }

    public function testFindContentLocales(): void
    {
        $page = $this->createShadowPage('test', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->setResolveConcreteLocales(true)->getMapping()
        );

        $this->assertEquals(['de'], $result->getContentLocales());
    }

    public function testFindNonExistingProperty(): void
    {
        $page = $this->createShadowPage('test', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['testProperty'])->getMapping()
        );

        $this->assertEquals('', $result['testProperty']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     *
     * @return PageDocument
     */
    private function createPage(
        $title,
        $locale,
        $data = [],
        $parentDocument = null,
        array $permissions = [],
        bool $publish = true
    ) {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');

        if (!$parentDocument) {
            $parentDocument = $this->homeDocument;
        }

        $document->setParent($parentDocument);

        $data['title'] = $title;
        $data['url'] = '/' . $title;

        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::NONE);
        $document->setShadowLocaleEnabled(false);
        $document->getStructure()->bind($data);
        $document->setPermissions($permissions);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'auto_create' => true,
            ]
        );

        if ($publish) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return $document;
    }

    /**
     * @param string $title
     * @param string $locale
     * @param string $shadowedLocale
     *
     * @return PageDocument
     */
    private function createShadowPage($title, $locale, $shadowedLocale)
    {
        $document1 = $this->createPage($title, $locale);
        $document = $this->documentManager->find(
            $document1->getUuid(),
            $shadowedLocale,
            ['load_ghost_content' => false]
        );

        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setShadowLocaleEnabled(true);
        $document->setTitle(\strrev($title));
        $document->setShadowLocale($locale);
        $document->setLocale($shadowedLocale);
        $document->setResourceSegment($document1->getResourceSegment());

        $this->documentManager->persist($document, $shadowedLocale);
        $this->documentManager->publish($document, $shadowedLocale);
        $this->documentManager->flush();

        return $document;
    }

    private function createInternalLinkPage($title, $locale, PageDocument $link, $publish = true)
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::INTERNAL);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setRedirectTarget($link);
        $document->getStructure()->bind($data);
        $document->setParent($this->homeDocument);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'auto_create' => true,
            ]
        );

        if ($publish) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return $document;
    }

    private function createRole(string $name, string $system)
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->em->persist($role);

        return $role;
    }
}
