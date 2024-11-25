<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\AccountResourceLoader;

class AccountResourceLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<AccountManager>
     */
    private ObjectProphecy $accountManager;

    private AccountResourceLoader $loader;

    public function setUp(): void
    {
        $this->accountManager = $this->prophesize(AccountManager::class);
        $this->loader = new AccountResourceLoader($this->accountManager->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('account', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $account1 = $this->createAccount(1);
        $account2 = $this->createAccount(3);

        $this->accountManager->getByIds([1, 3], 'en')->willReturn([
            $account1,
            $account2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $account1,
            3 => $account2,
        ], $result);
    }

    private static function createAccount(int $id): AccountApi
    {
        $account = new Account();
        $account->setId($id);

        return new AccountApi($account, 'en');
    }
}
