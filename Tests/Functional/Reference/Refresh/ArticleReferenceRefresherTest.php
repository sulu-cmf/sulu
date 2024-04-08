<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Reference\Refresh;

use Sulu\Bundle\ArticleBundle\Reference\Refresh\ArticleReferenceRefresher;
use Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\Document\ArticleDocument;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class ArticleReferenceRefresherTest extends SuluTestCase
{
    /**
     * @var ArticleReferenceRefresher
     */
    private $articleReferenceRefresher;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var EntityRepository<Reference>
     */
    private $referenceRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        if (!\interface_exists(ReferenceRefresherInterface::class)) {
            return;
        }
        $this->articleReferenceRefresher = $this->getContainer()->get('sulu_article.article_reference_refresher');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');
    }

    public function testRefreshWithoutReferences(): void
    {
        if (!\interface_exists(ReferenceRefresherInterface::class)) {
            $this->markTestSkipped('References did not exist in Sulu <2.6.');
        }

        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('Example article');
        $article->setStructureType('default_image');
        $this->documentManager->persist($article, 'en');
        $this->documentManager->publish($article, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->articleReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        // flush the references
        $this->getEntityManager()->flush();
        $this->assertSame(3, $count);

        self::assertCount(0, $this->referenceRepository->findAll());
    }

    public function testRefresh(): void
    {
        if (!\interface_exists(ReferenceRefresherInterface::class)) {
            $this->markTestSkipped('References did not exist in Sulu <2.6.');
        }

        $media = $this->createMedia();
        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('Example article');
        $article->setStructureType('default_image');
        $article->getStructure()->bind(['image' => ['id' => $media->getId()]]);
        $this->documentManager->persist($article, 'en');
        $this->documentManager->publish($article, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->articleReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        // flush the references
        $this->getEntityManager()->flush();
        $this->assertSame(3, $count);

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy([
            'referenceResourceKey' => 'articles',
            'referenceResourceId' => $article->getUuid(),
            'referenceLocale' => 'en',
        ]);

        self::assertCount(2, $references);

        self::assertSame('image', $references[0]->getReferenceProperty());
        self::assertSame((string) $media->getId(), $references[0]->getResourceId());
        self::assertSame('media', $references[0]->getResourceKey());
        self::assertSame($article->getUuid(), $references[0]->getReferenceResourceId());
        self::assertSame('articles', $references[0]->getReferenceResourceKey());
        self::assertSame('en', $references[0]->getReferenceLocale());
        self::assertSame('website', $references[0]->getReferenceContext());

        self::assertSame('image', $references[1]->getReferenceProperty());
        self::assertSame((string) $media->getId(), $references[1]->getResourceId());
        self::assertSame('media', $references[1]->getResourceKey());
        self::assertSame($article->getUuid(), $references[1]->getReferenceResourceId());
        self::assertSame('articles', $references[1]->getReferenceResourceKey());
        self::assertSame('en', $references[1]->getReferenceLocale());
        self::assertSame('admin', $references[1]->getReferenceContext());
    }

    private function createMedia(): Media
    {
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $mediaType = new MediaType();
        $mediaType->setName('Default Media Type');

        $collection = new Collection();
        $collection->setType($collectionType);

        $media = new Media();
        $media->setType($mediaType);
        $media->setCollection($collection);

        $this->getEntityManager()->persist($collection);
        $this->getEntityManager()->persist($collectionType);
        $this->getEntityManager()->persist($mediaType);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        return $media;
    }
}
