<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Reference\Refresh;

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Reference\Refresh\SnippetReferenceRefresher;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class SnippetReferenceRefresherTest extends SuluTestCase
{
    private SnippetReferenceRefresher $snippetReferenceRefresher;
    private DocumentManagerInterface $documentManager;

    /**
     * @var EntityRepository<Reference>
     */
    private EntityRepository $referenceRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->snippetReferenceRefresher = $this->getContainer()->get('sulu_snippet.snippet_reference_refresher');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');
    }

    public function testRefreshWithoutReferences(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setTitle('Example snippet');
        $snippet->setStructureType('car');
        $this->documentManager->persist($snippet, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->snippetReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        // flush the references
        $this->getEntityManager()->flush();
        $this->assertSame(4, $count);

        self::assertCount(0, $this->referenceRepository->findAll());
    }

    public function testRefresh(): void
    {
        $media = $this->createMedia();
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setTitle('Example snippet');
        $snippet->setStructureType('car');
        $snippet->getStructure()->bind(['image' => ['id' => $media->getId()]]);
        $this->documentManager->persist($snippet, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->snippetReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        // flush the references
        $this->getEntityManager()->flush();
        $this->assertSame(4, $count);

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy([
            'referenceResourceKey' => 'snippets',
            'referenceResourceId' => $snippet->getUuid(),
            'referenceLocale' => 'en',
        ]);

        self::assertCount(1, $references);

        self::assertSame('image', $references[0]->getReferenceProperty());
        self::assertSame((string) $media->getId(), $references[0]->getResourceId());
        self::assertSame('media', $references[0]->getResourceKey());
        self::assertSame($snippet->getUuid(), $references[0]->getReferenceResourceId());
        self::assertSame('snippets', $references[0]->getReferenceResourceKey());
        self::assertSame('en', $references[0]->getReferenceLocale());
        self::assertSame('admin', $references[0]->getReferenceContext());
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
