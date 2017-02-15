<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Jackalope\Query\Query;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManager;

/**
 * Repository class for snippets.
 *
 * Responsible for retrieving snippets from the content repository
 *
 * TODO: Remove SessionManager and ContentMapper dependencies.
 *       - Switch to the DocumentManager query builder;
 *       - Return referrers from DocumentInspector;
 */
class SnippetRepository
{
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param SessionManager $sessionManager
     * @param ContentMapper  $contentMapper
     */
    public function __construct(SessionManager $sessionManager, ContentMapper $contentMapper, DocumentManager $documentManager)
    {
        $this->contentMapper = $contentMapper;
        $this->sessionManager = $sessionManager;
        $this->documentManager = $documentManager;
    }

    /**
     * Return the nodes which refer to the structure with the
     * given UUID.
     *
     * @param string $uuid
     *
     * @return \PHPCR\NodeInterface[]
     */
    public function getReferences($uuid)
    {
        $session = $this->sessionManager->getSession();
        $node = $session->getNodeByIdentifier($uuid);

        return iterator_to_array($node->getReferences());
    }

    /**
     * Return snippets identified by the given UUIDs.
     *
     * UUIDs which fail to resolve to a snippet will be ignored.
     *
     * @param array  $uuids
     * @param string $locale
     * @param bool $loadGhostContent
     *
     * @return SnippetDocument
     */
    public function getSnippetsByUuids(array $uuids, $locale, $loadGhostContent = false)
    {
        $snippets = [];

        foreach ($uuids as $uuid) {
            try {
                $snippet = $this->documentManager->find($uuid, $locale, [
                    'load_ghost_content' => $loadGhostContent,
                ]);
                $snippets[] = $snippet;
            } catch (DocumentNotFoundException $e) {
                // ignore not found items
            }
        }

        return $snippets;
    }

    /**
     * Return snippets.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $locale
     * @param string $type         Optional snippet type
     * @param int    $offset       Optional offset
     * @param int    $max          Optional max
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @throws \InvalidArgumentException
     *
     * @return SnippetDocument[]
     */
    public function getSnippets(
        $locale,
        $type = null,
        $offset = null,
        $max = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $query = $this->getSnippetsQuery($locale, $type, $offset, $max, $search, $sortBy, $sortOrder);
        $documents = $this->documentManager->createQuery($query, $locale, [
            'load_ghost_content' => true,
        ])->execute();

        return $documents;
    }

    /**
     * Return snippets amount.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $locale
     * @param string $type         Optional snippet type
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @throws \InvalidArgumentException
     *
     * @return SnippetBridge[]
     */
    public function getSnippetsAmount(
        $locale,
        $type = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $query = $this->getSnippetsQuery($locale, $type, null, null, $search, $sortBy, $sortOrder);
        $result = $query->execute();

        return count(iterator_to_array($result->getRows()));
    }

    /**
     * Copy snippet from src-locale to dest-locale.
     *
     * TODO: We currently need the content mapper to copy the locale, it should be
     *       removed, see https://github.com/sulu-io/sulu/issues/1998
     *
     * @param string $uuid
     * @param int $userId
     * @param string $srcLocale
     * @param string $destLocales
     *
     * @return SnippetBridge
     */
    public function copyLocale($uuid, $userId, $srcLocale, $destLocales)
    {
        return $this->contentMapper->copyLanguage(
            $uuid,
            $userId,
            null,
            $srcLocale,
            $destLocales,
            Structure::TYPE_SNIPPET
        );
    }

    /**
     * Return snippets load query.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $locale
     * @param string $type         Optional snippet type
     * @param int    $offset       Optional offset
     * @param int    $max          Optional max
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @return Query
     */
    private function getSnippetsQuery(
        $locale,
        $type = null,
        $offset = null,
        $max = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $snippetNode = $this->sessionManager->getSnippetNode($type);
        $workspace = $this->sessionManager->getSession()->getWorkspace();
        $queryManager = $workspace->getQueryManager();

        $qf = $queryManager->getQOMFactory();
        $qb = new QueryBuilder($qf);

        $qb->from(
            $qb->qomf()->selector('a', 'nt:unstructured')
        );

        if (null === $type) {
            $qb->where(
                $qb->qomf()->descendantNode('a', $snippetNode->getPath())
            );
        } else {
            $qb->where(
                $qb->qomf()->childNode('a', $snippetNode->getPath())
            );
        }

        $qb->andWhere(
            $qb->qomf()->comparison(
                $qb->qomf()->propertyValue('a', 'jcr:mixinTypes'),
                QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                $qb->qomf()->literal('sulu:snippet')
            )
        );

        if (null !== $offset) {
            $qb->setFirstResult($offset);

            if (null === $max) {
                // we get zero results if no max specified
                throw new \InvalidArgumentException(
                    'If you specify an offset then you must also specify $max'
                );
            }

            $qb->setMaxResults($max);
        }

        if (null !== $search) {
            $search = str_replace('*', '%', $search);
            $searchConstraint = $qf->orConstraint(
                $qf->comparison(
                    $qf->propertyValue('a', 'i18n:' . $locale . '-title'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,
                    $qf->literal('%' . $search . '%')
                ),
                $qf->comparison(
                    $qf->propertyValue('a', 'template'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,
                    $qf->literal('%' . $search . '%')
                )
            );
            $qb->andWhere($searchConstraint);
        }

        // Title is a mandatory property for snippets
        // NOTE: Prefixing the language code and namespace here is bad. But the solution is
        //       refactoring (i.e. a node property name translator service).
        $sortOrder = ($sortOrder !== null ? strtoupper($sortOrder) : 'ASC');
        $sortBy = ($sortBy !== null ? $sortBy : 'title');

        $qb->orderBy(
            $qb->qomf()->propertyValue('a', 'i18n:' . $locale . '-' . $sortBy),
            $sortOrder !== null ? strtoupper($sortOrder) : 'ASC'
        );

        return $qb->getQuery();
    }
}
