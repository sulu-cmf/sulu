<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\SnippetBundle\Content\SnippetDataProvider;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SnippetDataProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContentQueryExecutorInterface>
     */
    private $contentQueryExecutor;

    /**
     * @var ObjectProphecy<ContentQueryBuilderInterface>
     */
    private $snippetQueryBuilder;

    /**
     * @var ObjectProphecy<SuluNodeHelper>
     */
    private $nodeHelper;

    /**
     * @var ObjectProphecy<LazyLoadingValueHolderFactory>
     */
    private $proxyFactory;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var SnippetDataProvider
     */
    private $snippetDataProvider;

    public function setUp(): void
    {
        $this->contentQueryExecutor = $this->prophesize(ContentQueryExecutorInterface::class);
        $this->snippetQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->proxyFactory = $this->prophesize(LazyLoadingValueHolderFactory::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->proxyFactory->createProxy(Argument::cetera())
            ->willReturn($this->prophesize(VirtualProxyInterface::class)->reveal());

        $this->snippetDataProvider = new SnippetDataProvider(
            $this->contentQueryExecutor->reveal(),
            $this->snippetQueryBuilder->reveal(),
            $this->nodeHelper->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentManager->reveal(),
            $this->referenceStore->reveal()
        );

        $this->referenceStore->getAll()->willReturn([]);
    }

    public function testEnabledAudienceTargeting(): void
    {
        $provider = new SnippetDataProvider(
            $this->contentQueryExecutor->reveal(),
            $this->snippetQueryBuilder->reveal(),
            $this->nodeHelper->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentManager->reveal(),
            $this->referenceStore->reveal(),
            true
        );

        $configuration = $provider->getConfiguration();

        $this->assertTrue($configuration->hasAudienceTargeting());
    }

    public function testDisabledAudienceTargeting(): void
    {
        $provider = new SnippetDataProvider(
            $this->contentQueryExecutor->reveal(),
            $this->snippetQueryBuilder->reveal(),
            $this->nodeHelper->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentManager->reveal(),
            $this->referenceStore->reveal(),
            false
        );

        $configuration = $provider->getConfiguration();

        $this->assertFalse($configuration->hasAudienceTargeting());
    }

    public function testGetTypesConfiguration(): void
    {
        /** @var TokenStorageInterface|ObjectProphecy $tokenStorage */
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        /** @var FormMetadataProvider|ObjectProphecy $formMetadataProvider */
        $formMetadataProvider = $this->prophesize(FormMetadataProvider::class);

        /** @var TokenInterface|ObjectProphecy $token */
        $token = $this->prophesize(TokenInterface::class);
        /** @var UserInterface|ObjectProphecy $user */
        $user = $this->prophesize(UserInterface::class);

        $tokenStorage->getToken()
            ->shouldBeCalled()
            ->willReturn($token->reveal());
        $token->getUser()
            ->shouldBeCalled()
            ->willReturn($user);
        $user->getLocale()
            ->shouldBeCalled()
            ->willReturn('en');

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setName('template-1');
        $formMetadata1->setTitle('translated-template-1');

        $formMetadata2 = new FormMetadata();
        $formMetadata2->setName('template-2');
        $formMetadata2->setTitle('translated-template-2');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('template-1', $formMetadata1);
        $typedFormMetadata->addForm('template-2', $formMetadata2);

        $formMetadataProvider->getMetadata('snippet', 'en', [])
            ->shouldBeCalled()
            ->willReturn($typedFormMetadata);

        $provider = new SnippetDataProvider(
            $this->contentQueryExecutor->reveal(),
            $this->snippetQueryBuilder->reveal(),
            $this->nodeHelper->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentManager->reveal(),
            $this->referenceStore->reveal(),
            false,
            $formMetadataProvider->reveal(),
            $tokenStorage->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);

        $this->assertCount(2, $configuration->getTypes());
        $this->assertSame('template-1', $configuration->getTypes()[0]->getValue());
        $this->assertSame('translated-template-1', $configuration->getTypes()[0]->getName());
        $this->assertSame('template-2', $configuration->getTypes()[1]->getValue());
        $this->assertSame('translated-template-2', $configuration->getTypes()[1]->getName());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideResolveDataItems')]
    public function testResolveDataItems(
        $filters,
        $propertyParameter,
        $options,
        $limit,
        $page,
        $pageSize,
        $result,
        $hasNextPage
    ): void {
        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            $limit ?: ($pageSize ? $pageSize + 1 : null),
            $pageSize ? $pageSize * ($page - 1) : null
        )->willReturn($result);

        if (\array_key_exists('type', $propertyParameter)) {
            $this->nodeHelper->getBaseSnippetUuid($propertyParameter['type'])->willReturn('some-uuid');
        } else {
            $this->nodeHelper->getBaseSnippetUuid(null)->willReturn(null);
        }

        $this->snippetQueryBuilder->init([
            'config' => [
                'excluded' => null,
                'dataSource' => \array_key_exists('type', $propertyParameter) ? 'some-uuid' : null,
                'includeSubFolders' => true,
            ],
            'properties' => [],
            'excluded' => [],
        ])->shouldBeCalled();

        $dataProviderResult = $this->snippetDataProvider->resolveDataItems(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );

        $this->assertCount(\count($result), $dataProviderResult->getItems());
        $this->assertEquals($hasNextPage, $dataProviderResult->getHasNextPage());
    }

    public static function provideResolveDataItems()
    {
        return [
            [['excluded' => null], [], ['webspaceKey' => 'sulu', 'locale' => 'de'], null, 1, null, [], false],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                1,
                null,
                [['id' => 1], ['id' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                1,
                1,
                null,
                [['id' => 1]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['id' => 1], ['id' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                ['type' => 'default'],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['id' => 1], ['id' => 2]],
                false,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideResolveDataItems')]
    public function testResolveResourceItems(
        $filters,
        $propertyParameter,
        $options,
        $limit,
        $page,
        $pageSize,
        $result,
        $hasNextPage
    ): void {
        foreach ($result as $item) {
            $this->referenceStore->add($item['id'])->shouldBeCalled();
        }

        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            $limit ?: ($pageSize ? $pageSize + 1 : null),
            $pageSize ? $pageSize * ($page - 1) : null
        )->willReturn($result);

        $dataProviderResult = $this->snippetDataProvider->resolveResourceItems(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );

        $this->assertCount(\count($result), $dataProviderResult->getItems());
        $this->assertEquals($hasNextPage, $dataProviderResult->getHasNextPage());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideResolveExcludeDuplicates')]
    public function testResolveResourceItemsExcludeDuplicates($filters, $uuids): void
    {
        $options = ['webspaceKey' => 'sulu', 'locale' => 'de'];

        $this->referenceStore->getAll()->willReturn(['456-456-456']);

        $result = [];
        foreach ($uuids as $uuid) {
            $result[] = ['id' => $uuid];
            $this->referenceStore->add($uuid)->shouldBeCalled();
        }

        $this->snippetQueryBuilder->init(
            [
                'config' => \array_merge($filters, ['dataSource' => null, 'includeSubFolders' => true]),
                'properties' => [],
                'excluded' => ['456-456-456'],
            ]
        )->shouldBeCalled();

        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            null,
            null
        )->willReturn($result);

        $dataProviderResult = $this->snippetDataProvider->resolveResourceItems(
            $filters,
            ['exclude_duplicates' => new PropertyParameter('exclude_duplicates', true)],
            $options
        );

        $this->assertCount(\count($result), $dataProviderResult->getItems());
        $this->assertEquals(false, $dataProviderResult->getHasNextPage());
    }

    public static function provideResolveExcludeDuplicates()
    {
        return [
            [
                [],
                ['123-123-123', '321-321-321'],
            ],
            [
                ['excluded' => null],
                ['123-123-123', '321-321-321'],
            ],
            [
                ['excluded' => []],
                ['123-123-123', '321-321-321'],
            ],
        ];
    }

    public function provideResolveResourceItems()
    {
        return [
            [['excluded' => null], [], ['webspaceKey' => 'sulu', 'locale' => 'de'], null, 1, null, [], false],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                1,
                null,
                [['id' => 1], ['id' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                1,
                1,
                null,
                [['id' => 1]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['id' => 1], ['id' => 2]],
                false,
            ],
        ];
    }
}
