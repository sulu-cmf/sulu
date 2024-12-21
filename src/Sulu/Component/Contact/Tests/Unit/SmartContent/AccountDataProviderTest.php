<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Tests\Unit\SmartContent;

use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Contact\SmartContent\AccountDataItem;
use Sulu\Component\Contact\SmartContent\AccountDataProvider;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

class AccountDataProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<DataProviderRepositoryInterface>
     */
    private $dataProviderRepository;

    /**
     * @var ObjectProphecy<ArraySerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var AccountDataProvider
     */
    private $accountDataProvider;

    public function setUp(): void
    {
        $this->dataProviderRepository = $this->prophesize(DataProviderRepositoryInterface::class);
        $this->serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->accountDataProvider = new AccountDataProvider(
            $this->dataProviderRepository->reveal(),
            $this->serializer->reveal(),
            $this->referenceStore->reveal()
        );
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->accountDataProvider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter(): void
    {
        $parameter = $this->accountDataProvider->getDefaultPropertyParameter();

        $this->assertEquals([], $parameter);
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         tags: string[],
     *     },
     *     1: int|null,
     *     2: int,
     *     3: int,
     *     4: Account[],
     *     5: bool,
     *     6: AccountDataItem[],
     * }>
     */
    public static function dataItemsDataProvider(): iterable
    {
        $accounts = [
            self::createAccount(1, 'Massive Art'),
            self::createAccount(2, 'Sulu'),
            self::createAccount(3, 'Apple'),
        ];

        $dataItems = [];
        foreach ($accounts as $account) {
            $dataItems[] = self::createDataItem($account);
        }

        return [
            [['tags' => ['A']], null, 1, 3, $accounts, false, $dataItems],
            [['tags' => ['A']], null, 1, 2, $accounts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => ['A']], 5, 1, 2, $accounts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => ['A']], 1, 1, 2, \array_slice($accounts, 0, 1), false, \array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @param array{
     *     tags: string[],
     * } $filters
     * @param int|null $limit
     * @param int $page
     * @param int $pageSize
     * @param Account[] $repositoryResult
     * @param bool $hasNextPage
     * @param AccountDataItem[] $items
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataItemsDataProvider')]
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items): void
    {
        $this->dataProviderRepository->findByFilters(
            $filters,
            $page,
            $pageSize,
            $limit,
            'en',
            [],
            null,
            null
        )->willReturn($repositoryResult);

        $result = $this->accountDataProvider->resolveDataItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         tags: string[],
     *     },
     *     1: int|null,
     *     2: int,
     *     3: int,
     *     4: Account[],
     *     5: bool,
     *     6: ArrayAccessItem[],
     * }>
     */
    public static function resourceItemsDataProvider(): iterable
    {
        $accounts = [
            self::createAccount(1, 'Massive Art'),
            self::createAccount(2, 'Sulu'),
            self::createAccount(3, 'Apple'),
        ];

        $dataItems = [];
        foreach ($accounts as $account) {
            $dataItems[] = self::createResourceItem($account);
        }

        return [
            [['tags' => ['A']], null, 1, 3, $accounts, false, $dataItems],
            [['tags' => ['A']], null, 1, 2, $accounts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => ['A']], 5, 1, 2, $accounts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => ['A']], 1, 1, 2, \array_slice($accounts, 0, 1), false, \array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @param array{
     *      tags: string[],
     *  } $filters
     * @param int|null $limit
     * @param int $page
     * @param int $pageSize
     * @param Account[] $repositoryResult
     * @param bool $hasNextPage
     * @param AccountDataItem[] $items
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('resourceItemsDataProvider')]
    public function testResolveResourceItems(
        $filters,
        $limit,
        $page,
        $pageSize,
        $repositoryResult,
        $hasNextPage,
        $items
    ): void {
        $serializeCallback = function(Account $account) {
            return self::serialize($account);
        };

        $context = SerializationContext::create()->setSerializeNull(true)->setGroups(
            ['fullAccount', 'partialContact', 'partialCategory']
        );

        $this->serializer->serialize(Argument::type(Account::class), $context)
            ->will(
                function($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $this->dataProviderRepository->findByFilters(
            $filters,
            $page,
            $pageSize,
            $limit,
            'en',
            [],
            null,
            null
        )->willReturn($repositoryResult);

        $result = $this->accountDataProvider->resolveResourceItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    public function testResolveDataSource(): void
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->accountDataProvider = new AccountDataProvider(
            $this->getRepository(),
            $serializer->reveal(),
            $referenceStore->reveal()
        );

        $this->assertNull($this->accountDataProvider->resolveDatasource('', [], []));
    }

    /**
     * @param array{
     *     tags?: string[],
     * } $filters
     * @param int $page
     * @param int $pageSize
     * @param int|null $limit
     * @param AccountDataItem[] $result
     * @param array<string, mixed> $options
     *
     * @return DataProviderRepositoryInterface
     */
    private function getRepository(
        $filters = [],
        $page = null,
        $pageSize = 0,
        $limit = null,
        $result = [],
        $options = []
    ) {
        $mock = $this->prophesize(DataProviderRepositoryInterface::class);

        $mock->findByFilters($filters, $page, $pageSize, $limit, 'en', $options, null)->willReturn($result);

        return $mock->reveal();
    }

    private static function createAccount($id, $name, $tags = []): Account
    {
        $entity = new AccountEntity();
        self::setPrivateProperty($entity, 'id', $id);
        $entity->setNumber($id);
        $entity->setName($name);
        foreach ($tags as $tag) {
            $entity->addTag($tag);
        }
        $entity->setPlaceOfJurisdiction('');
        $entity->setUid('');
        $entity->setCorporation('');
        $entity->setCreated(new \DateTime());
        $entity->setChanged(new \DateTime());

        return new Account($entity, 'de');
    }

    private static function createDataItem(Account $account): AccountDataItem
    {
        return new AccountDataItem($account);
    }

    private static function createResourceItem(Account $account): ArrayAccessItem
    {
        return new ArrayAccessItem($account->getId(), self::serialize($account), $account);
    }

    private static function serialize(Account $account)
    {
        $tags = [];
        foreach ($account->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return [
            'number' => $account->getNumber(),
            'name' => $account->getName(),
            'registerNumber' => $account->getNumber(),
            'placeOfJurisdiction' => $account->getPlaceOfJurisdiction(),
            'uid' => $account->getUid(),
            'corporation' => $account->getCorporation(),
            'created' => $account->getCreated(),
            'changed' => $account->getChanged(),
            'medias' => $account->getMedias(),
            'emails' => [],
            'phones' => [],
            'faxes' => [],
            'urls' => [],
            'tags' => $tags,
            'categories' => [],
        ];
    }
}
