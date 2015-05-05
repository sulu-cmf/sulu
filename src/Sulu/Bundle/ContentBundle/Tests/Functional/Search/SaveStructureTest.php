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

use Sulu\Bundle\ContentBundle\Tests\Fixtures\SecondStructureCache;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

class SaveStructureTest extends BaseTestCase
{
    /**
     * Check that the automatic indexing works
     */
    public function testSaveStructure()
    {
        $this->indexStructure('About Us', '/about-us');

        $searchManager = $this->getSearchManager();
        $res = $searchManager->createSearch('About')->locale('de')->index('page')->execute();
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('/about-us', $document->getUrl());
        $this->assertEquals(null, $document->getDescription());
    }

    public function testSaveStructureWithBlocks()
    {
        $mapper = $this->getContainer()->get('sulu.content.mapper');

        $data = array(
            'title' => 'Places',
            'url' => '/places',
            'block' => array(
                array(
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                ),
                array(
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                ),
            ),
        );

        $request = ContentMapperRequest::create()
            ->setData($data)
            ->setTemplateKey('blocks')
            ->setWebspaceKey('sulu_io')
            ->setLocale('de')
            ->setUserId(1)
            ->setPartialUpdate(true)
            ->setUuid(null)
            ->setParentUuid(null)
            ->setState(Structure::STATE_PUBLISHED);

        $mapper->saveRequest($request);

        $searchManager = $this->getSearchManager();

        $searches = array(
            'Places' => 1,
            'Basel' => 1,
            'Dornbirn' => 1,
        );

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    /**
     * Test that the tagged "description" field is indexed.
     */
    public function testSaveSecondStructure()
    {
        $searchManager = $this->getSearchManager();

        $structure = new SecondStructureCache();
        $structure->setUuid(123);
        $structure->getProperty('title')->setValue('This is a title Giraffe');
        $articleProperty = $structure->getProperty('article');
        $articleProperty->setValue('out with colleagues. Following a highly publicised appeal for information on her');
        $articleProperty->addTag(new PropertyTag('sulu.search.field', array()));
        $structure->getProperty('url')->setValue('/this/is/a/url');
        $structure->getProperty('images')->setValue(array('asd'));
        $structure->setLanguageCode('de');
        $structure->setNodeState(StructureInterface::STATE_PUBLISHED);

        $searchManager->index($structure);

        $res = $searchManager->createSearch('Giraffe')->locale('de')->index('page')->execute();
        $this->assertCount(1, $res);

        $structure->getProperty('title')->setValue('Pen and Paper');
        $searchManager->index($structure);

        // $res = $searchManager->createSearch('Pen')->locale('de')->index('content')->execute();
        // $this->assertCount(1, $res);
    }
}
