<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Twig;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureResolverInterface>
     */
    private $structureResolver;

    /**
     * @var ObjectProphecy<ContentMapperInterface>
     */
    private $contentMapper;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $session;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $parentNode;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $startPageNode;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private $logger;

    /**
     * @var ContentTwigExtension
     */
    private $extension;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<Webspace>
     */
    private $webspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->startPageNode = $this->prophesize(NodeInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);

        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()
            ->willReturn('sulu_test');

        $this->webspaceManager->findWebspaceByKey('sulu_test')->willReturn($this->webspace->reveal());

        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('en', 'us'));

        $this->sessionManager->getSession()->willReturn($this->session->reveal());
        $this->sessionManager->getContentNode('sulu_test')->willReturn($this->startPageNode->reveal());

        $this->session->getNodeByIdentifier('123-123-123')->willReturn($this->node->reveal());
        $this->session->getNodeByIdentifier('321-321-321')->willReturn($this->parentNode->reveal());

        $this->node->getIdentifier()->willReturn('123-123-123');
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(4);

        $this->parentNode->getIdentifier()->willReturn('321-321-321');
        $this->parentNode->getDepth()->willReturn(3);

        $this->startPageNode->getDepth()->willReturn(3);

        $this->extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            null,
            []
        );
    }

    public function testLoadWithoutProperties(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $result = $this->extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithoutPropertiesWithPermissions(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(true)->shouldBeCalledOnce();

        $security = $this->prophesize(Security::class);
        $security->getSystem()->willReturn('Website')->shouldBeCalled();

        $this->webspace->getSecurity()->willReturn($security->reveal())->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(true)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure);

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $result = $this->extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithoutPropertiesWithoutPermissions(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $security = $this->prophesize(Security::class);
        $security->getSystem()->willReturn('Website')->shouldBeCalled();

        $this->webspace->getSecurity()->willReturn($security->reveal())->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(true)->shouldBeCalled();

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                PageAdmin::SECURITY_CONTEXT_PREFIX . 'sulu_test',
                'en_us',
                SecurityBehavior::class,
                '123-123-123',
                'Website'
            ),
            PermissionTypes::VIEW
        )
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure);

        $result = $this->extension->load('123-123-123');

        $this->assertEquals(null, $result);
    }

    public function testLoadWithoutPropertiesNonWebspaceBehaviorDocument(): void
    {
        $snippetDocument = $this->prophesize(StructureBehavior::class);

        $testStructure = $this->prophesize(SnippetBridge::class);
        $testStructure->getDocument()->willReturn($snippetDocument);

        $this->securityChecker->hasPermission(Argument::cetera())->shouldNotBeCalled();
        $this->webspaceManager->findWebspaceByKey(Argument::cetera())->shouldNotBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure);

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $result = $this->extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithDeprecatedRequestStack(): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $requestStack->reveal()
        );

        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];

        $currentRequest = $this->prophesize(Request::class);
        $requestStack->getCurrentRequest()->willReturn($currentRequest->reveal());
        $subRequest = $this->prophesize(Request::class);
        $currentRequest->duplicate([], [], null, null, [])->willReturn($subRequest->reveal());
        $requestStack->push($subRequest->reveal())->shouldBeCalled();

        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $requestStack->pop()->shouldBeCalled();

        $result = $extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithRequestStack(): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            null,
            null,
            $requestStack->reveal()
        );

        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];

        $currentRequest = $this->prophesize(Request::class);
        $requestStack->getCurrentRequest()->willReturn($currentRequest->reveal());
        $subRequest = $this->prophesize(Request::class);
        $currentRequest->duplicate([], [], null, null, [])->willReturn($subRequest->reveal());
        $requestStack->push($subRequest->reveal())->shouldBeCalled();

        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $requestStack->pop()->shouldBeCalled();

        $result = $extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithRequestStackAndException(): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            null,
            null,
            $requestStack->reveal()
        );

        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $currentRequest = $this->prophesize(Request::class);
        $requestStack->getCurrentRequest()->willReturn($currentRequest->reveal());
        $subRequest = $this->prophesize(Request::class);
        $currentRequest->duplicate([], [], null, null, [])->willReturn($subRequest->reveal());
        $requestStack->push($subRequest->reveal())->shouldBeCalled();

        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException());

        $requestStack->pop()->shouldBeCalled();

        $this->expectException(\RuntimeException::class);
        $extension->load('123-123-123');
    }

    public function testLoadWithProperties(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
        ])->shouldBeCalled();

        $result = $this->extension->load('123-123-123', ['property-1', 'invalid-property-name']);

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'property-1' => 'view',
                ],
                'content' => [
                    'property-1' => 'content',
                ],
            ],
            $result
        );
    }

    public function testLoadWithPropertiesWithKeys(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
        ])->shouldBeCalled();

        $result = $this->extension->load(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'invalidProperty' => 'invalid-property-name']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                ],
            ],
            $result
        );
    }

    public function testLoadWithPropertiesIncludingExcerpt(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            true,
            ['property-1']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'extension' => [
                'excerpt' => ['title' => 'test-title', 'description' => 'test-description'],
            ],
        ])->shouldBeCalled();

        $result = $this->extension->load(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'excerptTitle' => 'excerpt.title']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                    'excerptTitle' => [],
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                    'excerptTitle' => 'test-title',
                ],
            ],
            $result
        );
    }

    public function testLoadWithEnabledUrlsAttribute(): void
    {
        $this->extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            null,
            ['urls' => true]
        );

        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'urls' => [
                'en' => '/english-url',
                'de' => '/german-url',
            ],
        ])->shouldBeCalled();

        $result = $this->extension->load('123-123-123', ['property-1', 'invalid-property-name']);

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'property-1' => 'view',
                ],
                'content' => [
                    'property-1' => 'content',
                ],
                'urls' => [
                    'en' => '/english-url',
                    'de' => '/german-url',
                ],
            ],
            $result
        );
    }

    public function testLoadWithDisabledUrlsAttribute(): void
    {
        $this->extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            null,
            ['urls' => false]
        );

        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'urls' => [
                'en' => '/english-url',
                'de' => '/german-url',
            ],
        ])->shouldBeCalled();

        $result = $this->extension->load('123-123-123', ['property-1', 'invalid-property-name']);

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'property-1' => 'view',
                ],
                'content' => [
                    'property-1' => 'content',
                ],
            ],
            $result
        );
    }

    public function testLoadNull(): void
    {
        $this->contentMapper->load(Argument::cetera())->shouldNotBeCalled();

        $this->assertNull($this->extension->load(null));
    }

    public function testLoadNotExistingDocument(): void
    {
        $documentNotFoundException = $this->prophesize(DocumentNotFoundException::class);
        $documentNotFoundException->__toString()->willReturn('something');

        $this->contentMapper->load(Argument::cetera())->willThrow($documentNotFoundException->reveal());

        $this->assertNull($this->extension->load('999-999-999'));
    }

    public function testLoadParentWithoutProperties(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('321-321-321', 'sulu_test', 'en_us')->willReturn($testStructure);

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal(), true, null)
            ->willReturn($resolvedStructure)
            ->shouldBeCalled();

        $result = $this->extension->loadParent('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadParentWithProperties(): void
    {
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $pageDocument->willImplement(SecurityBehavior::class);

        $testStructure = $this->prophesize(StructureBridge::class);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $this->webspace->getSecurity()->willReturn(null)->shouldBeCalled();
        $this->webspace->hasWebsiteSecurity()->willReturn(false)->shouldBeCalled();

        $this->contentMapper->load('321-321-321', 'sulu_test', 'en_us')->willReturn($testStructure);

        $this->structureResolver->resolve($testStructure->reveal(), true, ['property-1'])->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'extension' => [
                'excerpt' => ['title' => 'test-title', 'description' => 'test-description'],
            ],
        ])->shouldBeCalled();

        $result = $this->extension->loadParent(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'excerptTitle' => 'excerpt.title']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                    'excerptTitle' => [],
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                    'excerptTitle' => 'test-title',
                ],
            ],
            $result
        );
    }

    public function testLoadParentStartPage(): void
    {
        $this->expectException(ParentNotFoundException::class);
        $this->expectExceptionMessage('Parent for "321-321-321" not found (perhaps it is the startpage?)');

        $this->extension->loadParent('321-321-321');
    }
}
