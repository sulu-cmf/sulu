<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Reference\Provider;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Reference\Provider\ArticleReferenceProvider;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class ArticleReferenceProviderTest extends SuluTestCase
{
    /**
     * @var ArticleReferenceProvider
     */
    private $articleReferenceProvider;

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

        $this->articleReferenceProvider = $this->getContainer()->get('sulu_article.reference_provider');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');
    }

    public function testUpdateReferences(): void
    {
        if (!\interface_exists(ReferenceRefresherInterface::class)) {
            $this->markTestSkipped('References did not exist in Sulu <2.6.');
        }

        $media = $this->createMedia();
        /** @var \Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\Document\ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('Example article');
        $article->setStructureType('default_image');
        $article->getStructure()->bind(['image' => ['id' => $media->getId()]]);
        $this->documentManager->persist($article, 'en');
        $this->documentManager->publish($article, 'en');
        $this->documentManager->flush();

        $this->articleReferenceProvider->updateReferences($article, 'en', 'test');
        $this->getEntityManager()->flush();

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy(['referenceContext' => 'test']);
        $this->assertCount(1, $references);
        self::assertSame((string) $media->getId(), $references[0]->getResourceId());
    }

    public function testUpdateUnpublishedReferences(): void
    {
        if (!\interface_exists(ReferenceRefresherInterface::class)) {
            $this->markTestSkipped('References did not exist in Sulu <2.6.');
        }

        $media = $this->createMedia();
        /** @var \Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\Document\ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('Example article');
        $article->setStructureType('default_image');
        $article->getStructure()->bind(['image' => ['id' => $media->getId()]]);
        $this->documentManager->persist($article, 'en');
        $this->documentManager->publish($article, 'en');
        $this->documentManager->flush();

        $this->documentManager->unpublish($article, 'en');
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::ensureKernelShutdown();
        static::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE]);
        // refresh services from new kernel
        $this->articleReferenceProvider = $this->getContainer()->get('sulu_article.reference_provider');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');

        /** @var ArticleDocument $article */
        $article = $this->documentManager->find($article->getUuid(), 'en', [
            'load_ghost_content' => false,
        ]);

        $this->articleReferenceProvider->updateReferences($article, 'en', 'test');
        $this->getEntityManager()->flush();

        $references = $this->referenceRepository->findBy(['referenceContext' => 'test']);
        $this->assertCount(0, $references);
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
