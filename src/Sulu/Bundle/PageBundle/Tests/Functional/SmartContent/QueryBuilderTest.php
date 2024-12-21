<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\SmartContent;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupCondition;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRule;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspace;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\SmartContent\QueryBuilder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class QueryBuilderTest extends SuluTestCase
{
    /**
     * @var ContentQueryExecutor
     */
    private $contentQuery;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * @var TagInterface
     */
    private $tag1;

    /**
     * @var TagInterface
     */
    private $tag2;

    /**
     * @var TagInterface
     */
    private $tag3;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $audienceTargetGroupRepository;

    /**
     * @var RoleInterface
     */
    private $anonymousRoleSecurity;

    /**
     * @var RoleInterface
     */
    private $anonymousRoleNoSecurity;

    public function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->initPhpcr();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_page.extension.manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->contentQuery = $this->getContainer()->get('sulu_page_test.query_executor');
        $this->tagRepository = $this->getContainer()->get('sulu.repository.tag');
        $this->audienceTargetGroupRepository = $this->getContainer()->get('sulu.repository.target_group');

        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $user = $this->getContainer()->get('test_user_provider')->getUser();

        $this->anonymousRoleSecurity = $this->getContainer()->get('sulu.repository.role')->createNew();
        $this->anonymousRoleSecurity->setName('Anonymous');
        $this->anonymousRoleSecurity->setAnonymous(true);
        $this->anonymousRoleSecurity->setSystem('test_security_system');

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($this->anonymousRoleSecurity);
        $permission1->setContext('sulu.webspaces.sulu_io');
        $em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setPermissions(122);
        $permission2->setRole($this->anonymousRoleSecurity);
        $permission2->setContext('sulu.webspaces.test_io');
        $em->persist($permission2);

        $em->persist($this->anonymousRoleSecurity);

        $this->anonymousRoleNoSecurity = $this->getContainer()->get('sulu.repository.role')->createNew();
        $this->anonymousRoleNoSecurity->setName('Anonymous 2');
        $this->anonymousRoleNoSecurity->setAnonymous(true);
        $this->anonymousRoleNoSecurity->setSystem('sulu');

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($this->anonymousRoleNoSecurity);
        $permission1->setContext('sulu.webspaces.sulu_io');
        $em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setPermissions(122);
        $permission2->setRole($this->anonymousRoleNoSecurity);
        $permission2->setContext('sulu.webspaces.test_io');
        $em->persist($permission2);

        $em->persist($this->anonymousRoleNoSecurity);
        $em->flush();

        $this->tag1 = $this->tagRepository->createNew();
        $this->tag1->setName('test1');
        $this->tag1->setCreator($user);
        $this->tag1->setChanger($user);
        $em->persist($this->tag1);

        $this->tag2 = $this->tagRepository->createNew();
        $this->tag2->setName('test2');
        $this->tag2->setCreator($user);
        $this->tag2->setChanger($user);
        $em->persist($this->tag2);

        $this->tag3 = $this->tagRepository->createNew();
        $this->tag3->setName('test3');
        $this->tag3->setCreator($user);
        $this->tag3->setChanger($user);
        $em->persist($this->tag3);

        $em->flush();

        $this->getContainer()->get('sulu_security.system_store')->setSystem('sulu_io');
    }

    public function propertiesProvider()
    {
        $documents = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            $data = [
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i,
                'ext' => [
                    'excerpt' => [
                        'title' => 'Excerpt Title ' . $i,
                        'tags' => [],
                    ],
                ],
            ];
            $template = 'simple';

            if ($i > 2 * $max / 3) {
                $template = 'block';
                $data['article'] = [
                    [
                        'title' => 'Block Title ' . $i,
                        'article' => 'Blockarticle ' . $i,
                        'type' => 'test',
                        'settings' => [],
                    ],
                    [
                        'title' => 'Block Title 2 ' . $i,
                        'article' => 'Blockarticle2 ' . $i,
                        'type' => 'test',
                        'settings' => [],
                    ],
                ];
            } elseif ($i > $max / 3) {
                $template = 'article';
                $data['article'] = 'Text article ' . $i;
            }

            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle($data['title']);
            $document->setResourceSegment($data['url']);
            $document->getStructure()->bind($data);
            $document->setStructureType($template);
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);

            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');

            $documents[$document->getUuid()] = $document;
        }

        $this->documentManager->flush();

        return $documents;
    }

    public function testProperties(): void
    {
        $documents = $this->propertiesProvider();

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'properties' => [
                    'my_article' => new PropertyParameter('my_article', 'article'),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        foreach ($result as $item) {
            /** @var PageDocument $expectedDocument */
            $expectedDocument = $documents[$item['id']];

            $this->assertEquals($expectedDocument->getUuid(), $item['id']);
            $this->assertEquals($expectedDocument->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expectedDocument->getChanged(), $item['changed']);
            $this->assertEquals($expectedDocument->getChanger(), $item['changer']);
            $this->assertEquals($expectedDocument->getCreated(), $item['created']);
            $this->assertEquals($expectedDocument->getCreator(), $item['creator']);
            $this->assertEquals($expectedDocument->getLocale(), $item['locale']);
            $this->assertEquals($expectedDocument->getStructureType(), $item['template']);

            $this->assertEquals($expectedDocument->getPath(), '/cmf/sulu_io/contents' . $item['path']);

            $this->assertEquals($expectedDocument->getTitle(), $item['title']);
            $this->assertEquals($expectedDocument->getResourceSegment(), $item['url']);

            if ($expectedDocument->getStructure()->hasProperty('article')) {
                $this->assertEquals(
                    $expectedDocument->getStructure()->getProperty('article')->getValue(),
                    $item['my_article']
                );
            }
        }
    }

    public function datasourceProvider()
    {
        /** @var PageDocument $news */
        $news = $this->documentManager->create('page');
        $news->setTitle('News');
        $news->setResourceSegment('/news');
        $news->setStructureType('simple');
        $news->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($news, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($news, 'en');
        $this->documentManager->flush();

        /** @var PageDocument $products */
        $products = $this->documentManager->create('page');
        $products->setTitle('Products');
        $products->setResourceSegment('/products');
        $products->setStructureType('simple');
        $products->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($products, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($products, 'en');
        $this->documentManager->flush();

        $documents = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle('News ' . $i);
            $document->setResourceSegment('/news/news-' . $i);
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $document->setParent($news);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents/news']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return [$news, $products, $documents];
    }

    public function testDatasource(): void
    {
        list($news, $products, $nodes) = $this->datasourceProvider();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        // test news
        $builder->init(['config' => ['dataSource' => $news->getUuid()]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(\count($nodes), \count($result));
        foreach ($result as $item) {
            /** @var PageDocument $expectedDocument */
            $expectedDocument = $nodes[$item['id']];

            $this->assertEquals($expectedDocument->getUuid(), $item['id']);
            $this->assertEquals($expectedDocument->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expectedDocument->getPath(), '/cmf/sulu_io/contents' . $item['path']);
            $this->assertEquals($expectedDocument->getTitle(), $item['title']);
        }

        // test products
        $builder->init(['config' => ['dataSource' => $products->getUuid()]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(0, \count($result));
    }

    public function testIncludeSubFolder(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($news, $products, $nodes) = $this->datasourceProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['config' => ['dataSource' => $root->getIdentifier(), 'includeSubFolders' => true]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        // nodes + news + products
        $this->assertEquals(\count($nodes) + 2, \count($result));

        $nodes[$news->getUuid()] = $news;
        $nodes[$products->getUuid()] = $products;

        for ($i = 0; $i < \count($nodes); ++$i) {
            $item = $result[$i];

            /** @var StructureInterface $expected */
            $expected = $nodes[$item['id']];

            $this->assertEquals($expected->getUuid(), $item['id']);
            $this->assertEquals($expected->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), '/cmf/sulu_io/contents' . $item['path']);
            $this->assertEquals($expected->getTitle(), $item['title']);
        }
    }

    public function testSecurity(): void
    {
        $webspace = $this->getContainer()->get('sulu_core.webspace.webspace_manager')
            ->findWebspaceByKey('test_io');

        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->getContainer()->get('request_stack')->push($request);

        $root = $this->sessionManager->getContentNode('test_io');

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Document');
        $document->setResourceSegment('/document');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/test_io/contents']);
        $this->documentManager->publish($document, 'en');

        $securedDocument = $this->documentManager->create('page');
        $securedDocument->setTitle('Secured document');
        $securedDocument->setResourceSegment('/secured-document');
        $securedDocument->setStructureType('simple');
        $securedDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $securedDocument->setPermissions([$this->anonymousRoleSecurity->getId() => ['view' => false]]);
        $this->documentManager->persist($securedDocument, 'en', ['parent_path' => '/cmf/test_io/contents']);
        $this->documentManager->publish($securedDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'dataSource' => $root->getIdentifier(),
            ],
        ]);

        $result = $this->contentQuery->execute('test_io', ['en'], $builder, true, -1, null, null, false, 64);
        $this->assertCount(1, $result);
        $this->assertEquals('Document', $result[0]['title']);
    }

    public function testSecurityWithoutPermissionCheck(): void
    {
        $webspace = $this->getContainer()->get('sulu_core.webspace.webspace_manager')
            ->findWebspaceByKey('sulu_io');

        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->getContainer()->get('request_stack')->push($request);

        $root = $this->sessionManager->getContentNode('sulu_io');

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Document');
        $document->setResourceSegment('/document');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');

        $securedDocument = $this->documentManager->create('page');
        $securedDocument->setTitle('Secured document');
        $securedDocument->setResourceSegment('/secured-document');
        $securedDocument->setStructureType('simple');
        $securedDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $securedDocument->setPermissions([$this->anonymousRoleSecurity->getId() => ['view' => false]]);
        $this->documentManager->persist($securedDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($securedDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'dataSource' => $root->getIdentifier(),
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder, true, -1, null, null, false, 64);
        $this->assertCount(2, $result);
        $this->assertEquals('Document', $result[0]['title']);
        $this->assertEquals('Secured document', $result[1]['title']);
    }

    public function testAudienceTargeting(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        /** @var PageDocument $familyDocument */
        $familyDocument = $this->documentManager->create('page');
        $familyDocument->setTitle('Family');
        $familyDocument->setResourceSegment('/family');
        $familyDocument->setExtensionsData(
            [
                'excerpt' => ['audience_targeting_groups' => [1]],
            ]
        );
        $familyDocument->setStructureType('simple');
        $familyDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($familyDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($familyDocument, 'en');

        $singleDocument = $this->documentManager->create('page');
        $singleDocument->setTitle('Single');
        $singleDocument->setResourceSegment('/single');
        $singleDocument->setExtensionsData(
            [
                'audience_targeting-groups' => [],
            ]
        );
        $singleDocument->setStructureType('simple');
        $singleDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($singleDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($singleDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'targetGroupId' => 1,
                'dataSource' => $root->getIdentifier(),
                'includeSubFolders' => true,
                'audienceTargeting' => true,
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertCount(1, $result);
        $this->assertEquals('Family', $result[0]['title']);
    }

    public function testAudienceTargetingDeactivated(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');

        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->audienceTargetGroupRepository->createNew();
        $targetGroup->setTitle('Test');
        $targetGroup->setPriority(5);
        $targetGroup->setActive(true);
        $targetGroupWebspace = new TargetGroupWebspace();
        $targetGroupWebspace->setWebspaceKey('sulu_io');
        $targetGroupWebspace->setTargetGroup($targetGroup);
        $targetGroupRule = new TargetGroupRule();
        $targetGroupRule->setTitle('Test');
        $targetGroupRule->setFrequency(TargetGroupRuleInterface::FREQUENCY_SESSION);
        $targetGroupRule->setTargetGroup($targetGroup);
        $targetGroupCondition = new TargetGroupCondition();
        $targetGroupCondition->setType('locale');
        $targetGroupCondition->setCondition(['locale' => 'en']);
        $targetGroupCondition->setRule($targetGroupRule);
        $this->getEntityManager()->persist($targetGroup);
        $this->getEntityManager()->persist($targetGroupWebspace);
        $this->getEntityManager()->persist($targetGroupRule);
        $this->getEntityManager()->persist($targetGroupCondition);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        /** @var PageDocument $familyDocument */
        $familyDocument = $this->documentManager->create('page');
        $familyDocument->setTitle('Family');
        $familyDocument->setResourceSegment('/family');
        $familyDocument->setExtensionsData(
            [
                'excerpt' => ['audience_targeting_groups' => [$targetGroup->getId()]],
            ]
        );
        $familyDocument->setStructureType('simple');
        $familyDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($familyDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($familyDocument, 'en');

        $singleDocument = $this->documentManager->create('page');
        $singleDocument->setTitle('Single');
        $singleDocument->setResourceSegment('/single');
        $singleDocument->setExtensionsData(
            [
                'audience_targeting-groups' => [],
            ]
        );
        $singleDocument->setStructureType('simple');
        $singleDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($singleDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($singleDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'dataSource' => $root->getIdentifier(),
                'includeSubFolders' => true,
                'audienceTargeting' => false,
                'sortBy' => 'title',
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertCount(2, $result);
        $this->assertEquals('Family', $result[0]['title']);
        $this->assertEquals('Single', $result[1]['title']);
    }

    public function testAudienceTargetingDeactivatedTargetGroupEvaluator(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');

        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->audienceTargetGroupRepository->createNew();
        $targetGroup->setTitle('Test');
        $targetGroup->setPriority(5);
        $targetGroup->setActive(true);
        $targetGroupWebspace = new TargetGroupWebspace();
        $targetGroupWebspace->setWebspaceKey('sulu_io');
        $targetGroupWebspace->setTargetGroup($targetGroup);
        $targetGroupRule = new TargetGroupRule();
        $targetGroupRule->setTitle('Test');
        $targetGroupRule->setFrequency(TargetGroupRuleInterface::FREQUENCY_SESSION);
        $targetGroupRule->setTargetGroup($targetGroup);
        $targetGroupCondition = new TargetGroupCondition();
        $targetGroupCondition->setType('locale');
        $targetGroupCondition->setCondition(['locale' => 'en']);
        $targetGroupCondition->setRule($targetGroupRule);
        $this->getEntityManager()->persist($targetGroup);
        $this->getEntityManager()->persist($targetGroupWebspace);
        $this->getEntityManager()->persist($targetGroupRule);
        $this->getEntityManager()->persist($targetGroupCondition);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        /** @var PageDocument $familyDocument */
        $familyDocument = $this->documentManager->create('page');
        $familyDocument->setTitle('Family');
        $familyDocument->setResourceSegment('/family');
        $familyDocument->setExtensionsData(
            [
                'excerpt' => ['audience_targeting_groups' => [$targetGroup->getId()]],
            ]
        );
        $familyDocument->setStructureType('simple');
        $familyDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($familyDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($familyDocument, 'en');

        $singleDocument = $this->documentManager->create('page');
        $singleDocument->setTitle('Single');
        $singleDocument->setResourceSegment('/single');
        $singleDocument->setExtensionsData(
            [
                'audience_targeting-groups' => [],
            ]
        );
        $singleDocument->setStructureType('simple');
        $singleDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($singleDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($singleDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'dataSource' => $root->getIdentifier(),
                'includeSubFolders' => true,
                'audienceTargeting' => true,
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $titles = \array_map(
            function($item) {
                return $item['title'];
            },
            $result
        );

        $this->assertCount(2, $titles);
        $this->assertContains('Family', $titles);
        $this->assertContains('Single', $titles);
    }

    public function testSegment(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        /** @var PageDocument $skiingDocument */
        $skiingDocument = $this->documentManager->create('page');
        $skiingDocument->setTitle('Skiing');
        $skiingDocument->setResourceSegment('/skiing');
        $skiingDocument->setExtensionsData(
            [
                'excerpt' => ['segments' => ['sulu_io' => 'w']],
            ]
        );
        $skiingDocument->setStructureType('simple');
        $skiingDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($skiingDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($skiingDocument, 'en');

        $paraglidingDocument = $this->documentManager->create('page');
        $paraglidingDocument->setTitle('Paragliding');
        $paraglidingDocument->setResourceSegment('/paragliding');
        $paraglidingDocument->setExtensionsData(
            [
                'excerpt' => ['segments' => ['sulu_io' => 's']],
            ]
        );
        $paraglidingDocument->setStructureType('simple');
        $paraglidingDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($paraglidingDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($paraglidingDocument, 'en');

        $hikingDocument = $this->documentManager->create('page');
        $hikingDocument->setTitle('Hiking');
        $hikingDocument->setResourceSegment('/hiking');
        $hikingDocument->setStructureType('simple');
        $hikingDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($hikingDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($hikingDocument, 'en');

        $this->documentManager->flush();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'segmentKey' => 'w',
                'dataSource' => $root->getIdentifier(),
                'includeSubFolders' => true,
                'audienceTargeting' => true,
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertCount(2, $result);

        $resultTitles = \array_map(function($row) {
            return $row['title'];
        }, $result);
        $this->assertContains('Hiking', $resultTitles);
        $this->assertContains('Skiing', $resultTitles);

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init([
            'config' => [
                'segmentKey' => 's',
                'dataSource' => $root->getIdentifier(),
                'includeSubFolders' => true,
                'audienceTargeting' => true,
            ],
        ]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertCount(2, $result);

        $resultTitles = \array_map(function($row) {
            return $row['title'];
        }, $result);
        $this->assertContains('Hiking', $resultTitles);
        $this->assertContains('Paragliding', $resultTitles);
    }

    public function tagsProvider()
    {
        $documents = [];
        $max = 15;
        $t1t2 = 0;
        $t1 = 0;
        $t2 = 0;
        for ($i = 0; $i < $max; ++$i) {
            if (2 === $i % 3) {
                $tags = [$this->tag1->getName()];
                ++$t1;
            } elseif (1 === $i % 3) {
                $tags = [$this->tag1->getName(), $this->tag2->getName()];
                ++$t1t2;
            } else {
                $tags = [$this->tag2->getName()];
                ++$t2;
            }

            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle('News ' . \rand(1, 100));
            $document->setResourceSegment('/news/news-' . $i);
            $document->setExtensionsData(
                [
                    'excerpt' => [
                        'tags' => $tags,
                    ],
                ]
            );
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return [$documents, $t1, $t2, $t1t2];
    }

    public function testTags(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1, 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'tagOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, \count($result));

        // tag 1
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag1->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1, \count($result));

        // tag 2
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag2->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, \count($result));

        // tag 3
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag3->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(0, \count($result));
    }

    public function testWebsiteTags(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1 and 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, \count($result));

        // tag 1 or 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1 + $t2, \count($result));

        // tag 3 or 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag3->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t2 + $t1t2, \count($result)); // no t3 pages there

        // tag 1
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1, \count($result));

        // tag 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, \count($result));

        // tag 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag3->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(0, \count($result));
    }

    public function testTagsBoth(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId()],
                    'tagOperator' => 'and',
                    'websiteTags' => [$this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, \count($result));

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId()],
                    'tagOperator' => 'and',
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'OR',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, \count($result));
    }

    public function categoriesProvider()
    {
        $data = [
            [
                'title' => 'News 1',
                'url' => '/news-1',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1],
                    ],
                ],
            ],
            [
                'title' => 'News 2',
                'url' => '/news-2',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1, 2],
                    ],
                ],
            ],
            [
                'title' => 'News 3',
                'url' => '/news-3',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1, 3],
                    ],
                ],
            ],
            [
                'title' => 'News 4',
                'url' => '/news-4',
                'ext' => [
                    'excerpt' => [
                        'categories' => [3],
                    ],
                ],
            ],
        ];

        $documents = [];
        foreach ($data as $item) {
            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle($item['title']);
            $document->setResourceSegment($item['url']);
            $document->setExtensionsData($item['ext']);
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return $documents;
    }

    public function testCategories(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        $this->categoriesProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // category 1
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(3, \count($result));
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(3, \count($result));

        // category 1 and 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 2],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(1, \count($result));

        // category 1 or 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 3],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(4, \count($result));

        // category 1 and 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 3],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(1, \count($result));

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(4, \count($result));
    }

    public function orderByProvider()
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('A');
        $document->setResourceSegment('/a');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('Z');
        $document->setResourceSegment('/z');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('y');
        $document->setResourceSegment('/y');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('b');
        $document->setResourceSegment('/b');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $documents[$document->getResourceSegment()] = $document;
        $this->documentManager->flush();

        return [$documents];
    }

    public function testOrderBy(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by title
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'sortBy' => 'title']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('A', $result[0]['title']);
        $this->assertEquals('b', $result[1]['title']);
        $this->assertEquals('y', $result[2]['title']);
        $this->assertEquals('Z', $result[3]['title']);

        // order by title and desc
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'sortBy' => 'title',
                    'sortMethod' => 'desc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('Z', $result[0]['title']);
        $this->assertEquals('y', $result[1]['title']);
        $this->assertEquals('b', $result[2]['title']);
        $this->assertEquals('A', $result[3]['title']);
    }

    public function testOrderByOrder(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();
        $session = $this->sessionManager->getSession();

        $node = $session->getNodeByIdentifier($nodes['/y']->getUuid());
        $node->setProperty('sulu:order', 10);
        $node = $session->getNodeByIdentifier($nodes['/b']->getUuid());
        $node->setProperty('sulu:order', 20);
        $node = $session->getNodeByIdentifier($nodes['/a']->getUuid());
        $node->setProperty('sulu:order', 30);
        $node = $session->getNodeByIdentifier($nodes['/z']->getUuid());
        $node->setProperty('sulu:order', 40);
        $session->save();
        $session->refresh(false);

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by default
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'orderBy' => [],
                    'sortMethod' => 'asc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('y', $result[0]['title']);
        $this->assertEquals('b', $result[1]['title']);
        $this->assertEquals('A', $result[2]['title']);
        $this->assertEquals('Z', $result[3]['title']);

        // order by default
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'orderBy' => [],
                    'sortMethod' => 'desc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('Z', $result[0]['title']);
        $this->assertEquals('A', $result[1]['title']);
        $this->assertEquals('b', $result[2]['title']);
        $this->assertEquals('y', $result[3]['title']);
    }

    public function testOrderByOrderWithIncludeSubFolders(): void
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($news, $products, $nodes) = $this->datasourceProvider();
        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'orderBy' => [],
                    'includeSubFolders' => true,
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $paths = \array_map(
            function($item) {
                return $item['path'];
            },
            $result
        );

        $this->assertContains('/news', $paths);
        $this->assertContains('/news/news-0', $paths);
        $this->assertContains('/news/news-1', $paths);
        $this->assertContains('/news/news-10', $paths);
        $this->assertContains('/news/news-11', $paths);
        $this->assertContains('/news/news-12', $paths);
        $this->assertContains('/news/news-13', $paths);
        $this->assertContains('/news/news-14', $paths);
        $this->assertContains('/news/news-2', $paths);
        $this->assertContains('/news/news-3', $paths);
        $this->assertContains('/news/news-4', $paths);
        $this->assertContains('/news/news-5', $paths);
        $this->assertContains('/news/news-6', $paths);
        $this->assertContains('/news/news-7', $paths);
        $this->assertContains('/news/news-8', $paths);
        $this->assertContains('/news/news-9', $paths);
        $this->assertContains('/products', $paths);
    }

    public function testExtension(): void
    {
        /** @var PageDocument[] $documents */
        $documents = $this->propertiesProvider();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'properties' => [
                    'my_title' => new PropertyParameter('my_title', 'title'),
                    'ext_title' => new PropertyParameter('ext_title', 'excerpt.title'),
                    'ext_tags' => new PropertyParameter('ext_tags', 'excerpt.tags'),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        foreach ($result as $item) {
            $expectedDocument = $documents[$item['id']];

            $this->assertEquals($expectedDocument->getTitle(), $item['my_title']);
            $this->assertEquals($expectedDocument->getExtensionsData()['excerpt']['title'], $item['ext_title']);
            $this->assertEquals($expectedDocument->getExtensionsData()['excerpt']['tags'], $item['ext_tags']);
        }
    }

    public function testIds(): void
    {
        $nodes = $this->propertiesProvider();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['ids' => [\array_keys($nodes)[0], \array_keys($nodes)[1]]]);

        $tStart = \microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = \microtime(true) - $tStart;

        $this->assertEquals(2, \count($result));
        $this->assertArrayHasKey($result[0]['id'], $nodes);
        $this->assertArrayHasKey($result[1]['id'], $nodes);
    }

    public function testExcluded(): void
    {
        $nodes = $this->propertiesProvider();
        $uuids = \array_keys($nodes);

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['excluded' => [$uuids[0]]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(14, \count($result));
        unset($uuids[0]);
        foreach ($result as $item) {
            $this->assertContains($item['id'], $uuids);
        }
    }

    private function shadowProvider()
    {
        $nodesEn = [];
        $nodesDe = [];
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Team',
                    'url' => '/team',
                ],
                'en'
            )
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Thomas',
                    'url' => '/team/thomas',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid(),
                false,
                null,
                Structure::STATE_TEST
            )
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Daniel',
                    'url' => '/team/daniel',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid()
            )
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Johannes',
                    'url' => '/team/johannes',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid(),
                false,
                null,
                Structure::STATE_TEST
            )
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Alex',
                    'url' => '/team/alex',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid(),
                false,
                null
            )
        );

        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Team',
                    'url' => '/team',
                ],
                'de',
                $nodesEn['/team']->getUuid(),
                null,
                true,
                'en'
            )
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'url' => '/team/thomas',
                ],
                'de',
                $nodesEn['/team/thomas']->getUuid(),
                null,
                true,
                'en',
                Structure::STATE_TEST
            )
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'url' => '/team/daniel',
                ],
                'de',
                $nodesEn['/team/daniel']->getUuid(),
                null,
                true,
                'en'
            )
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Johannes DE',
                    'url' => '/team/johannes',
                ],
                'de',
                $nodesEn['/team/johannes']->getUuid()
            )
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important-2',
                    'url' => '/team/alex',
                ],
                'de',
                $nodesEn['/team/alex']->getUuid(),
                null,
                true,
                'en',
                Structure::STATE_TEST
            )
        );

        return ['en' => $nodesEn, 'de' => $nodesDe];
    }

    private function save(
        $data,
        $locale,
        $uuid = null,
        $parent = null,
        $isShadow = false,
        $shadowLocale = '',
        $state = WorkflowStage::PUBLISHED
    ) {
        if (!$isShadow) {
            /* @var PageDocument $document */
            try {
                $document = $uuid
                    ? $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false])
                    : $this->documentManager->create('page');
            } catch (DocumentNotFoundException $e) {
                $document = $this->documentManager->create('page');
            }
            $document->getStructure()->bind($data);
            $document->setTitle($data['title']);
            $document->setResourceSegment($data['url']);
            $document->setStructureType('simple');
            $document->setWorkflowStage($state);

            $persistOptions = [];
            if ($parent) {
                $document->setParent($this->documentManager->find($parent));
            } elseif (!$document->getParent()) {
                $persistOptions['parent_path'] = '/cmf/sulu_io/contents';
            }
            $this->documentManager->persist($document, $locale, $persistOptions);
        } else {
            $document = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            $document->setShadowLocaleEnabled(true);
            $document->setShadowLocale($shadowLocale);
            $document->setLocale($locale);
            $this->documentManager->persist($document, $locale);
        }

        if (WorkflowStage::PUBLISHED === $state) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return [$document->getResourceSegment() => $document];
    }

    public function testShadow(): void
    {
        $data = $this->shadowProvider();

        $builder = new QueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'ids' => [
                    $data['en']['/team/thomas']->getUuid(),
                    $data['en']['/team/daniel']->getUuid(),
                    $data['en']['/team/johannes']->getUuid(),
                    $data['en']['/team/alex']->getUuid(),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(4, \count($result));
        $this->assertEquals('/team/thomas', $result[0]['url']);
        $this->assertEquals('Thomas', $result[0]['title']);
        $this->assertEquals(false, $result[0]['publishedState']);
        $this->assertNull($result[0]['published']);
        $this->assertEquals('/team/daniel', $result[1]['url']);
        $this->assertEquals('Daniel', $result[1]['title']);
        $this->assertEquals(true, $result[1]['publishedState']);
        $this->assertNotNull($result[1]['published']);
        $this->assertEquals('/team/johannes', $result[2]['url']);
        $this->assertEquals('Johannes', $result[2]['title']);
        $this->assertEquals(false, $result[2]['publishedState']);
        $this->assertNull($result[2]['published']);
        $this->assertEquals('/team/alex', $result[3]['url']);
        $this->assertEquals('Alex', $result[3]['title']);
        $this->assertEquals(true, $result[3]['publishedState']);

        $result = $this->contentQuery->execute('sulu_io', ['de'], $builder);

        $this->assertEquals(4, \count($result));
        $this->assertEquals('/team/thomas', $result[0]['url']);
        $this->assertEquals('Thomas', $result[0]['title']);
        $this->assertEquals(false, $result[0]['publishedState']);
        $this->assertNull($result[0]['published']);
        $this->assertEquals('/team/daniel', $result[1]['url']);
        $this->assertEquals('Daniel', $result[1]['title']);
        $this->assertEquals(true, $result[1]['publishedState']);
        $this->assertNotNull($result[1]['published']);
        $this->assertEquals('/team/johannes', $result[2]['url']);
        $this->assertEquals('Johannes DE', $result[2]['title']);
        $this->assertEquals(true, $result[2]['publishedState']);
        $this->assertNotNull($result[2]['published']);
        $this->assertEquals('/team/alex', $result[3]['url']);
        $this->assertEquals('Alex', $result[3]['title']);
        $this->assertEquals(false, $result[3]['publishedState']);
    }
}
