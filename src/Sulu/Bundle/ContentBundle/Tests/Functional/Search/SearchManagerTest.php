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

use Sulu\Component\Content\Mapper\ContentMapperRequest;

class SearchManagerTest extends BaseTestCase
{
    /**
     * The search manager should update existing documents with the same IDs rather
     * than creating new documents
     */
    public function testSearchManager()
    {
        $nbResults = 10;
        $documents = $this->generateStructureIndex($nbResults);

        for ($i = 1; $i <= 2; $i++) {
            foreach ($documents as $document) {
                $this->documentManager->persist($document, 'de');
            }

            $res = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('page')->execute();

            $this->assertCount($nbResults, $res);
        }
    }

    public function testSearchByWebspace()
    {
        $this->generateStructureIndex(4, 'webspace_four');
        $this->generateStructureIndex(2, 'webspace_two');
        $result = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('page')->execute();
        $this->assertCount(6, $result);

        $firstHit = reset($result);
        $document = $firstHit->getDocument();
        $this->assertEquals('page', $document->getCategory());

        if (!$this->getContainer()->get('massive_search.adapter') instanceof \Massive\Bundle\SearchBundle\Search\Adapter\ZendLuceneAdapter) {
            $this->markTestSkipped('Skipping zend lucene specific test');

            return;
        }

        // TODO: This should should not be here
        $res = $this->getSearchManager()->createSearch('+webspaceKey:webspace_four Structure*')->locale('de')->index('page')->execute();
        $this->assertCount(4, $res);
    }
}
