<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

/**
 * factory to encapsulate account creation.
 */
class AccountFactory implements AccountFactoryInterface
{
    private string $entityName;

    public function __construct(string $entityName)
    {
        $this->entityName = $entityName;
    }

    public function createEntity()
    {
        $entityName = $this->entityName;

        return new $entityName();
    }

    public function createApiEntity(AccountInterface $account, $locale)
    {
        return new AccountApi($account, $locale);
    }
}
