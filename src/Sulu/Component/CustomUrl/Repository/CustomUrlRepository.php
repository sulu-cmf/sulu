<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

use Jackalope\Query\Row;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Repository enables direct access to custom-urls without document-manager.
 */
class CustomUrlRepository
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private ContentRepositoryInterface $contentRepository,
        private GeneratorInterface $generator,
        private UserManagerInterface $userManager,
    ) {
    }

    /**
     * Returns list of custom-url data-arrays.
     *
     * @param string $path
     *
     * @return \Iterator
     */
    public function findList($path, ?array $baseDomains = null)
    {
        // TODO pagination

        $session = $this->sessionManager->getSession();
        $queryManager = $session->getWorkspace()->getQueryManager();

        $qomFactory = $queryManager->getQOMFactory();
        $queryBuilder = new QueryBuilder($qomFactory);

        $queryBuilder->select('a', 'jcr:uuid', 'uuid');
        $queryBuilder->addSelect('a', 'title', 'title');
        $queryBuilder->addSelect('a', 'published', 'published');
        $queryBuilder->addSelect('a', 'domainParts', 'domainParts');
        $queryBuilder->addSelect('a', 'baseDomain', 'baseDomain');
        $queryBuilder->addSelect('a', 'sulu:content', 'targetDocument');
        $queryBuilder->addSelect('a', 'sulu:created', 'created');
        $queryBuilder->addSelect('a', 'sulu:creator', 'creator');
        $queryBuilder->addSelect('a', 'sulu:changed', 'changed');
        $queryBuilder->addSelect('a', 'sulu:changer', 'changer');

        $qomf = $queryBuilder->qomf();

        $queryBuilder->from($qomf->selector('a', 'nt:unstructured'));

        $queryBuilder->where(
            $qomf->comparison(
                $qomf->propertyValue('a', 'jcr:mixinTypes'),
                QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                $qomf->literal('sulu:custom_url')
            )
        );
        $queryBuilder->andWhere(
            $qomf->descendantNode('a', $path)
        );

        if ($baseDomains) {
            $queryBuilder->andWhere($this->createBaseDomainQuery($baseDomains, $qomf));
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        /** @var string[] $uuids */
        $uuids = \array_map(
            function(Row $item) {
                return $item->getValue('a.targetDocument');
            },
            \iterator_to_array($result->getRows())
        );

        $targets = $this->contentRepository->findByUuids(
            \array_unique($uuids),
            null,
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        return new RowsIterator(
            $result->getRows(),
            $result->getColumnNames(),
            $targets,
            $this->generator,
            $this->userManager
        );
    }

    private function createBaseDomainQuery(array $baseDomains, QueryObjectModelFactoryInterface $qomf)
    {
        $baseDomainQuery = null;
        foreach ($baseDomains as $baseDomain) {
            if (!$baseDomainQuery) {
                $baseDomainQuery = $qomf->comparison(
                    $qomf->propertyValue('a', 'baseDomain'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                    $qomf->literal($baseDomain)
                );

                continue;
            }

            $baseDomainQuery = $qomf->orConstraint(
                $baseDomainQuery,
                $baseDomainQuery = $qomf->comparison(
                    $qomf->propertyValue('a', 'baseDomain'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                    $qomf->literal($baseDomain)
                )
            );
        }

        return $baseDomainQuery;
    }

    /**
     * Returns list of custom-url data-arrays.
     *
     * @param string $path
     *
     * @return array<string>
     */
    public function findUrls($path)
    {
        $session = $this->sessionManager->getSession();
        $queryManager = $session->getWorkspace()->getQueryManager();

        $qomFactory = $queryManager->getQOMFactory();
        $queryBuilder = new QueryBuilder($qomFactory);

        $queryBuilder->addSelect('a', 'domainParts', 'domainParts')
            ->addSelect('a', 'baseDomain', 'baseDomain');

        $queryBuilder->from(
            $queryBuilder->qomf()->selector('a', 'nt:unstructured')
        );

        $queryBuilder->where(
            $queryBuilder->qomf()->comparison(
                $queryBuilder->qomf()->propertyValue('a', 'jcr:mixinTypes'),
                QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                $queryBuilder->qomf()->literal('sulu:custom_url')
            )
        );
        $queryBuilder->andWhere(
            $queryBuilder->qomf()->descendantNode('a', $path)
        );

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return \array_map(
            function(Row $item) {
                return $this->generator->generate(
                    $item->getValue('a.baseDomain'),
                    \json_decode($item->getValue('a.domainParts'), true)
                );
            },
            \iterator_to_array($result->getRows())
        );
    }
}
