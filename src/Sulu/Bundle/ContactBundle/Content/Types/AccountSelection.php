<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Content\Types;

use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class AccountSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    public function __construct(
        AccountManager $accountManager,
        private ReferenceStoreInterface $accountReferenceStore
    ) {
        $this->accountManager = $accountManager;

        parent::__construct('AccountSelection');
    }

    /**
     * @return Account[]
     */
    public function getContentData(PropertyInterface $property): array
    {
        $ids = $property->getValue();
        if (!\is_array($ids) || empty($ids)) {
            return [];
        }

        $accounts = $this->accountManager->getByIds($ids, $property->getStructure()->getLanguageCode());

        $idPositions = \array_flip($ids);
        \usort($accounts, function(Account $a, Account $b) use ($idPositions) {
            return $idPositions[$a->getId()] - $idPositions[$b->getId()];
        });

        return $accounts;
    }

    public function preResolve(PropertyInterface $property)
    {
        $ids = $property->getValue();
        if (!\is_array($ids) || empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->accountReferenceStore->add($id);
        }
    }
}
