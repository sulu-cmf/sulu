<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Search;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Content\ArticleSelectionContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;

/**
 * Unit testcases for ArticleSelection ContentType.
 */
class ArticleSelectionContentTypeTest extends TestCase
{
    public function testGetContentData()
    {
        $ids = ['123-123-123', '321-321-321'];
        $articles = \array_map(
            function($id) {
                return new ArticleViewDocument($id);
            },
            \array_reverse($ids)
        );

        $manager = $this->prophesize(Manager::class);
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureInterface::class);
        $repository = $this->prophesize(Repository::class);
        $search = $this->prophesize(Search::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $structure->getLanguageCode()->willReturn('de');
        $property->getStructure()->willReturn($structure->reveal());

        $property->getValue()->willReturn($ids);
        $manager->getRepository(ArticleViewDocument::class)->willReturn($repository->reveal());
        $repository->createSearch()->willReturn($search->reveal());
        $search->addQuery(
            Argument::that(
                function(IdsQuery $query) use ($ids) {
                    return $query->toArray() === [
                        'ids' => [
                            'values' => \array_map(
                                function($id) {
                                    return $id . '-de';
                                },
                                $ids
                            ),
                        ],
                    ];
                }
            )
        )->shouldBeCalled();
        $search->setSize(2)->shouldBeCalled();

        $repository->findDocuments($search->reveal())->willReturn($articles);

        $contentType = new ArticleSelectionContentType(
            $manager->reveal(),
            $referenceStore->reveal(),
            ArticleViewDocument::class
        );

        $result = $contentType->getContentData($property->reveal());

        $this->assertCount(2, $result);
        $this->assertEquals($ids[0], $result[0]->getUuid());
        $this->assertEquals($ids[1], $result[1]->getUuid());
    }

    public function testGetContentDataEmptyArray()
    {
        $ids = [];

        $manager = $this->prophesize(Manager::class);
        $property = $this->prophesize(PropertyInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $property->getValue()->willReturn($ids);
        $manager->getRepository(ArticleViewDocument::class)->shouldNotBeCalled();

        $contentType = new ArticleSelectionContentType(
            $manager->reveal(),
            $referenceStore->reveal(),
            ArticleViewDocument::class
        );

        $result = $contentType->getContentData($property->reveal());

        $this->assertCount(0, $result);
    }

    public function testGetContentDataNull()
    {
        $ids = null;

        $manager = $this->prophesize(Manager::class);
        $property = $this->prophesize(PropertyInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $property->getValue()->willReturn($ids);
        $manager->getRepository(ArticleViewDocument::class)->shouldNotBeCalled();

        $contentType = new ArticleSelectionContentType(
            $manager->reveal(),
            $referenceStore->reveal(),
            ArticleViewDocument::class
        );

        $result = $contentType->getContentData($property->reveal());

        $this->assertCount(0, $result);
    }
}
