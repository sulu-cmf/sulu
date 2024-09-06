<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\CustomUrlBundle\Trash\CustomUrlTrashItemHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\DocumentManager\Document\UnknownDocument;

class CustomUrlTrashItemHandlerTest extends SuluTestCase
{
    private EntityManagerInterface $entityManager;

    private CustomUrlTrashItemHandler $customUrlTrashItemHandler;

    public function setUp(): void
    {
        static::purgeDatabase();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->customUrlTrashItemHandler = static::getContainer()->get('sulu_custom_urls.custom_url_trash_item_handler');
    }

    public function testStoreAndRestore(): void
    {
        $customUrl1 = new CustomUrl();
        $customUrl1->setTitle('test-title-1');
        $customUrl1->setCreator(101);
        $customUrl1->setCreated(new \DateTime('1999-04-20'));
        $customUrl1->setBaseDomain('sulu-test.localhost/*/*');
        $customUrl1->setDomainParts(['custom-path-1', 'custom-path-2']);
        $customUrl1->setCanonical(true);
        $customUrl1->setRedirect(false);
        $customUrl1->setNoFollow(true);
        $customUrl1->setNoIndex(false);
        $customUrl1->setTargetDocument('23232323');
        $customUrl1->setTargetLocale('de');
        $customUrl1->setPublished(true);
        $this->entityManager->persist($customUrl1);

        $trashItem = $this->customUrlTrashItemHandler->store($customUrl1);
        $this->documentManager->remove($customUrl1);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalCustomUrlUuid, $trashItem->getResourceId());
        static::assertSame('test-title-1', $trashItem->getResourceTitle());
        static::assertSame('sulu.webspaces.sulu_io.custom-urls', $trashItem->getResourceSecurityContext());

        /** @var CustomUrlDocument $restoredCustomUrl */
        $restoredCustomUrl = $this->customUrlTrashItemHandler->restore($trashItem, []);

        /** @var UnknownDocument $restoredCustomUrlParent */
        $restoredCustomUrlParent = $restoredCustomUrl->getParent();

        /** @var UnknownDocument $restoredCustomUrlTarget */
        $restoredCustomUrlTarget = $restoredCustomUrl->getTargetDocument();

        static::assertSame('test-title-1', $restoredCustomUrl->getTitle());
        static::assertSame($customUrlItemsDocument->getUuid(), $restoredCustomUrlParent->getUuid());
        static::assertSame(101, $restoredCustomUrl->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredCustomUrl->getCreated()->format('c'));
        static::assertSame('sulu-test.localhost/*/*', $restoredCustomUrl->getBaseDomain());
        static::assertSame(['custom-path-1', 'custom-path-2'], $restoredCustomUrl->getDomainParts());
        static::assertTrue($restoredCustomUrl->isCanonical());
        static::assertFalse($restoredCustomUrl->isRedirect());
        static::assertTrue($restoredCustomUrl->isNoFollow());
        static::assertFalse($restoredCustomUrl->isNoIndex());
        static::assertSame($homepageDocument->getUuid(), $restoredCustomUrlTarget->getUuid());
        static::assertSame('de', $restoredCustomUrl->getTargetLocale());
        static::assertFalse($restoredCustomUrl->isPublished());
    }
}
