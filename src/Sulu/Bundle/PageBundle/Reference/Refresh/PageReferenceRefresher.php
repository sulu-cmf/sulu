<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Reference\Refresh;

use Jackalope\Query\Row;
use Jackalope\Query\RowIterator;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * @internal your code should not depend on this class
 *
 * @final
 */
class PageReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private SessionInterface $session,
        private WebspaceManagerInterface $webspaceManager,
        private DocumentManagerInterface $documentManager,
        private DocumentReferenceProviderInterface $documentReferenceProvider,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $webspaceKey = $webspace->getKey();

            $sql2 = \sprintf("SELECT [jcr:uuid] FROM [nt:unstructured] as document WHERE document.[jcr:mixinTypes] = 'sulu:%s'", 'page');
            $sql2 .= \sprintf(" AND (isdescendantnode(document, '/cmf/%s/contents') OR issamenode(document, '/cmf/%s/contents'))", $webspaceKey, $webspaceKey);

            $queryManager = $this->session->getWorkspace()->getQueryManager();
            $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
            $queryResult = $query->execute();

            /** @var RowIterator $rows */
            $rows = $queryResult->getRows();

            foreach ($webspace->getAllLocalizations() as $localization) {
                $locale = $localization->getLocale();
                /** @var Row $row */
                foreach ($rows as $row) {
                    /** @var string $uuid */
                    $uuid = $row->getValue('jcr:uuid');
                    /** @var (UuidBehavior&TitleBehavior&StructureBehavior)|null $document */
                    $document = $this->documentManager->find(
                        $uuid,
                        $locale,
                        [
                            'load_ghost_content' => false,
                        ]
                    );

                    if (!$document) {
                        continue;
                    }

                    $this->documentReferenceProvider->updateReferences($document, $locale, $this->suluContext);

                    yield $document;
                }

                $this->documentManager->clear(); // the cache is locale independent, so we need to clear between locale changes
            }
        }
    }
}
