<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

/**
 * @extends NestedTreeRepository<AccountInterface>
 */
class AccountRepository extends NestedTreeRepository implements DataProviderRepositoryInterface, AccountRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function findById(int $id): ?AccountInterface
    {
        /** @var AccountInterface */
        return $this->find($id);
    }

    public function findOneByContactId(int $contactId): AccountInterface
    {
        $qb = $this->createQueryBuilder('a')
            ->join(
                'a.accountContacts',
                'accountContacts',
                'WITH',
                'IDENTITY(accountContacts.contact) = :contactId AND accountContacts.main = TRUE'
            )
            ->setParameter('contactId', $contactId);
        $query = $qb->getQuery();

        return $query->getSingleResult();
    }

    public function findAccountOnly(int $id): ?AccountInterface
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->where('account.id = :accountId');

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function findAccountById(int $id, bool $contacts = false): ?AccountInterface
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.categories', 'categories')
                ->leftJoin('categories.translations', 'categoryTranslations')
                ->leftJoin('account.accountAddresses', 'accountAddresses')
                ->leftJoin('accountAddresses.address', 'addresses')
                ->leftJoin('addresses.addressType', 'addressType')
                ->leftJoin('account.parent', 'parent')
                ->leftJoin('account.urls', 'urls')
                ->leftJoin('urls.urlType', 'urlType')
                ->leftJoin('account.phones', 'phones')
                ->leftJoin('phones.phoneType', 'phoneType')
                ->leftJoin('account.emails', 'emails')
                ->leftJoin('emails.emailType', 'emailType')
                ->leftJoin('account.notes', 'notes')
                ->leftJoin('account.faxes', 'faxes')
                ->leftJoin('faxes.faxType', 'faxType')
                ->leftJoin('account.socialMediaProfiles', 'socialMediaProfiles')
                ->leftJoin('socialMediaProfiles.socialMediaProfileType', 'socialMediaProfileType')
                ->leftJoin('account.bankAccounts', 'bankAccounts')
                ->leftJoin('account.tags', 'tags')
                ->leftJoin('account.mainContact', 'mainContact')
                ->leftJoin('account.medias', 'medias')
                ->addSelect('mainContact')
                ->addSelect('categories')
                ->addSelect('categoryTranslations')
                ->addSelect('tags')
                ->addSelect('bankAccounts')
                ->addSelect('accountAddresses')
                ->addSelect('addresses')
                ->addSelect('addressType')
                ->addSelect('parent')
                ->addSelect('urls')
                ->addSelect('urlType')
                ->addSelect('phones')
                ->addSelect('phoneType')
                ->addSelect('emails')
                ->addSelect('emailType')
                ->addSelect('faxes')
                ->addSelect('faxType')
                ->addSelect('notes')
                ->addSelect('medias')
                ->where('account.id = :accountId');

            if (true === $contacts) {
                $qb->leftJoin('account.accountContacts', 'accountContacts')
                    ->leftJoin('accountContacts.contact', 'contacts')
                    ->leftJoin('accountContacts.position', 'position')
                    ->addSelect('position')
                    ->addSelect('accountContacts')
                    ->addSelect('contacts');
            }

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function findByIds(array $ids): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('account')
            ->leftJoin('account.categories', 'categories')
            ->leftJoin('categories.translations', 'categoryTranslations')
            ->leftJoin('account.accountAddresses', 'accountAddresses')
            ->leftJoin('accountAddresses.address', 'addresses')
            ->leftJoin('addresses.addressType', 'addressType')
            ->leftJoin('account.parent', 'parent')
            ->leftJoin('account.urls', 'urls')
            ->leftJoin('urls.urlType', 'urlType')
            ->leftJoin('account.phones', 'phones')
            ->leftJoin('phones.phoneType', 'phoneType')
            ->leftJoin('account.emails', 'emails')
            ->leftJoin('emails.emailType', 'emailType')
            ->leftJoin('account.notes', 'notes')
            ->leftJoin('account.faxes', 'faxes')
            ->leftJoin('faxes.faxType', 'faxType')
            ->leftJoin('account.bankAccounts', 'bankAccounts')
            ->leftJoin('account.tags', 'tags')
            ->leftJoin('account.mainContact', 'mainContact')
            ->leftJoin('account.medias', 'medias')
            ->addSelect('mainContact')
            ->addSelect('categories')
            ->addSelect('categoryTranslations')
            ->addSelect('tags')
            ->addSelect('bankAccounts')
            ->addSelect('accountAddresses')
            ->addSelect('addresses')
            ->addSelect('addressType')
            ->addSelect('parent')
            ->addSelect('urls')
            ->addSelect('urlType')
            ->addSelect('phones')
            ->addSelect('phoneType')
            ->addSelect('emails')
            ->addSelect('emailType')
            ->addSelect('faxes')
            ->addSelect('faxType')
            ->addSelect('notes')
            ->addSelect('medias')
            ->where('account.id IN (:accountIds)')
            ->orderBy('account.id', 'ASC');

        $query = $qb->getQuery();
        $query->setParameter('accountIds', $ids);

        try {
            return $query->getResult();
        } catch (NoResultException $ex) {
            return [];
        }
    }

    public function findByFilter(array $filter): ?array
    {
        $qb = $this->createQueryBuilder('account');

        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'id':
                    $qb->where('account.id IN (:ids)');
                    $qb->setParameter('ids', $value);
                    break;
            }
        }

        $query = $qb->getQuery();

        /** @var AccountInterface[] */
        return $query->getResult();
    }

    public function findAllSelect(array $fields = []): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->from($this->getEntityName(), 'account');

        foreach ($fields as $field) {
            $qb->addSelect('account.' . $field . ' AS ' . $field);
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    public function findAccountByIdAndDelete(int $id): ?AccountInterface
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.accountAddresses', 'accountAddresses')
                ->leftJoin('accountAddresses.address', 'addresses')
                ->leftJoin('account.children', 'children')
                ->leftJoin('addresses.addressType', 'addressType')
                ->leftJoin('addresses.contactAddresses', 'addressContactAddresses')
                ->leftJoin('addresses.accountAddresses', 'addressAccountAddresses')
                ->leftJoin('addressAccountAddresses.account', 'addressAccounts')
                ->leftJoin('addressContactAddresses.contact', 'addressContacts')
                ->leftJoin('account.parent', 'parent')
                ->leftJoin('account.urls', 'urls')
                ->leftJoin('urls.urlType', 'urlType')
                ->leftJoin('account.phones', 'phones')
                ->leftJoin('phones.contacts', 'phonesContacts')
                ->leftJoin('phones.accounts', 'phonesAccounts')
                ->leftJoin('phones.phoneType', 'phoneType')
                ->leftJoin('account.faxes', 'faxes')
                ->leftJoin('faxes.faxType', 'faxType')
                ->leftJoin('faxes.accounts', 'faxesAccounts')
                ->leftJoin('faxes.contacts', 'faxesContacts')
                ->leftJoin('account.emails', 'emails')
                ->leftJoin('emails.emailType', 'emailType')
                ->leftJoin('emails.contacts', 'emailsContacts')
                ->leftJoin('emails.accounts', 'emailsAccounts')
                ->leftJoin('account.notes', 'notes')
                ->leftJoin('account.bankAccounts', 'bankAccounts')
                ->leftJoin('account.accountContacts', 'accountContacts')
                ->leftJoin('accountContacts.contact', 'contacts')
                ->leftJoin('account.mainContact', 'mainContact')
                ->leftJoin('accountContacts.position', 'position')
                ->addSelect('position')
                ->addSelect('mainContact')
                ->addSelect('bankAccounts')
                ->addSelect('addresses')
                ->addSelect('children')
                ->addSelect('accountAddresses')
                ->addSelect('accountContacts')
                ->addSelect('contacts')
                ->addSelect('addressType')
                ->addSelect('parent')
                ->addSelect('urls')
                ->addSelect('urlType')
                ->addSelect('phones')
                ->addSelect('phoneType')
                ->addSelect('emails')
                ->addSelect('emailType')
                ->addSelect('faxes')
                ->addSelect('faxType')
                ->addSelect('faxesContacts')
                ->addSelect('emailsContacts')
                ->addSelect('phonesContacts')
                ->addSelect('addressContacts')
                ->addSelect('faxesAccounts')
                ->addSelect('emailsAccounts')
                ->addSelect('phonesAccounts')
                ->addSelect('addressAccounts')
                ->addSelect('addressContactAddresses')
                ->addSelect('addressAccountAddresses')
                ->addSelect('notes')
                ->where('account.id = :accountId');

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    public function countDistinctAccountChildrenAndContacts(int $id): array
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.children', 'children')
                ->leftJoin('account.accountContacts', 'accountContacts')
                ->leftJoin('accountContacts.contact', 'contacts')
                ->select('count(DISTINCT children.id) AS numChildren')
                ->addSelect('count(DISTINCT contacts.id) AS numContacts')
                ->where('account.id = :accountId');

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return 0;
        }
    }

    public function findChildrenAndContacts(int $id): ?AccountInterface
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->leftJoin('account.children', 'children')
                ->leftJoin('account.accountContacts', 'accountContacts')
                ->leftJoin('accountContacts.contact', 'contacts')
                ->select('account')
                ->addSelect('children')
                ->addSelect('accountContacts')
                ->addSelect('contacts')
                ->where('account.id = :accountId');

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * @return AccountInterface[]
     */
    public function findChildAccounts(int $id): array
    {
        try {
            $qb = $this->createQueryBuilder('account')
                ->where('account.parent = :accountId');

            $query = $qb->getQuery();
            $query->setParameter('accountId', $id);

            /** @var AccountInterface[] */
            return $query->getResult();
        } catch (NoResultException $ex) {
            return [];
        }
    }

    /**
     * Append joins to query builder for "findByFilters" function.
     */
    protected function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
    }

    public function createNew()
    {
        /** @var class-string<AccountInterface> $className */
        $className = $this->getClassName();

        return new $className();
    }
}
