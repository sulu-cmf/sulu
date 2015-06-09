<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Search;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Symfony\Component\Filesystem\Filesystem;

class BaseTestCase extends SuluTestCase
{
    protected $session;
    protected $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/../app/data');

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->webspaceDocument = $this->documentManager->find('/cmf/sulu_io/contents');
    }

    public function getSearchManager()
    {
        $searchManager = $this->getContainer()->get('massive_search.search_manager');

        return $searchManager;
    }

    public function generateStructureIndex($count)
    {
        $documents = array();
        for ($i = 1; $i <= $count; $i++) {
            $pageDocument = new PageDocument();
            $pageDocument->setParent($this->webspaceDocument);
            $pageDocument->setTitle('Structure Title ' . $i);
            $pageDocument->setWorkflowStage(WorkflowStage::PUBLISHED);

            $this->documentManager->persist($pageDocument, 'de');
            $documents[] = $pageDocument;
        }

        $this->documentManager->flush();

        return $documents;
    }

    public function indexStructure($title, $url)
    {
        /** @var ContentMapperInterface $mapper */
        $document = $this->documentManager->create('page');
        $document->setTitle($title);
        $document->setStructureType('default');
        $document->setParent($this->webspaceDocument);
        $document->setResourceSegment($url);
        $this->documentManager->persist($document, 'de');

        return $document;
    }
}
