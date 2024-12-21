<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ShadowCopyPropertiesSubscriberTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    public function setUp(): void
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->liveSession = $this->getContainer()->get('sulu_document_manager.live_session');

        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');

        $englishDocument = $this->documentManager->create('page');
        $englishDocument->setStructureType('default');
        $englishDocument->setParent($this->homeDocument);
        $englishDocument->setTitle('English page');
        $englishDocument->setResourceSegment('/english-page');
        $this->documentManager->persist($englishDocument, 'en');

        $this->documentManager->flush();

        $germanDocument = $this->documentManager->find($englishDocument->getUuid(), 'de');
        $germanDocument->setStructureType('default');
        $germanDocument->setParent($this->homeDocument);
        $germanDocument->setTitle('Deutsche Seite');
        $germanDocument->setResourceSegment('/deutsche-seite');
        $this->documentManager->persist($germanDocument, 'de');

        $this->documentManager->flush();

        $this->documentManager->clear();
        $this->session->refresh(false);
    }

    public function testCopyShadowPropertiesToShadow(): void
    {
        /** @var PageDocument $germanDocument */
        $germanDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'de');
        $germanDocument->setShadowLocale('en');
        $germanDocument->setShadowLocaleEnabled(true);

        $this->documentManager->persist($germanDocument, 'de');
        $this->documentManager->publish($germanDocument, 'de');
        $this->documentManager->flush();

        /** @var PageDocument $englishDocument */
        $englishDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'en');
        $englishDocument->setExtensionsData([
            'excerpt' => [
                'tags' => ['tag1', 'tag2'],
                'categories' => [42, 43],
            ],
        ]);
        $englishDocument->setNavigationContexts(['main']);
        $englishDocument->setAuthor(12);
        $englishDocument->setAuthored(new \DateTime('2016-01-01'));
        $englishDocument->setLastModified(new \DateTime('2016-01-02'));
        $englishDocument->setStructureType('default');

        $this->documentManager->persist($englishDocument, 'en');
        $this->documentManager->publish($englishDocument, 'en');
        $this->documentManager->flush();

        $tag1Id = $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => 'tag1'])?->getId();
        $tag2Id = $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => 'tag2'])?->getId();

        $this->assertNotNull($tag1Id);
        $this->assertNotNull($tag2Id);

        $this->session->refresh(false);
        $this->liveSession->refresh(false);

        foreach ([$this->session, $this->liveSession] as $session) {
            $node = $session->getNode('/cmf/sulu_io/contents/english-page');

            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:en-excerpt-tags'));
            $this->assertSame([$tag1Id, $tag2Id], $this->nodeGetArrayValue($node, 'i18n:en-excerpt-tags'));
            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:de-excerpt-tags'));
            $this->assertSame([$tag1Id, $tag2Id], $this->nodeGetArrayValue($node, 'i18n:de-excerpt-tags'));

            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:en-excerpt-categories'));
            $this->assertSame([42, 43], $this->nodeGetArrayValue($node, 'i18n:en-excerpt-categories'));
            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:de-excerpt-categories'));
            $this->assertSame([42, 43], $this->nodeGetArrayValue($node, 'i18n:de-excerpt-categories'));

            $this->assertCount(1, $this->nodeGetArrayValue($node, 'i18n:en-navContexts'));
            $this->assertEquals(['main'], $this->nodeGetArrayValue($node, 'i18n:en-navContexts'));
            $this->assertCount(1, $this->nodeGetArrayValue($node, 'i18n:de-navContexts'));
            $this->assertEquals(['main'], $this->nodeGetArrayValue($node, 'i18n:de-navContexts'));

            $this->assertSame(12, (int) $this->nodeGetIntValue($node, 'i18n:en-author'));
            $this->assertSame(12, (int) $this->nodeGetIntValue($node, 'i18n:de-author'));

            $this->assertSame('2016-01-01T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:en-authored')?->format('c'));
            $this->assertSame('2016-01-01T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:de-authored')?->format('c'));

            $this->assertSame('2016-01-02T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:en-lastModified')?->format('c'));
            $this->assertSame('2016-01-02T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:de-lastModified')?->format('c'));

            $this->assertSame('default', $this->nodeGetStringValue($node, 'i18n:en-template'));
            $this->assertSame('default', $this->nodeGetStringValue($node, 'i18n:de-template'));
        }
    }

    public function testCopyShadowPropertiesFromShadow(): void
    {
        /** @var PageDocument $englishDocument */
        $englishDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'en');
        $englishDocument->setExtensionsData([
            'excerpt' => [
                'tags' => ['tag1', 'tag2'],
                'categories' => [42, 43],
            ],
        ]);
        $englishDocument->setNavigationContexts(['main']);
        $englishDocument->setAuthor(12);
        $englishDocument->setAuthored(new \DateTime('2016-01-01'));
        $englishDocument->setLastModified(new \DateTime('2016-01-02'));
        $englishDocument->setStructureType('default');

        $this->documentManager->persist($englishDocument, 'en');
        $this->documentManager->publish($englishDocument, 'en');
        $this->documentManager->flush();

        $this->documentManager->clear();
        $this->session->refresh(false);

        /** @var PageDocument $germanDocument */
        $germanDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'de');
        $germanDocument->setShadowLocale('en');
        $germanDocument->setShadowLocaleEnabled(true);

        $this->documentManager->persist($germanDocument, 'de');
        $this->documentManager->publish($germanDocument, 'de');
        $this->documentManager->flush();

        $tag1Id = $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => 'tag1'])?->getId();
        $tag2Id = $this->getEntityManager()->getRepository(Tag::class)->findOneBy(['name' => 'tag2'])?->getId();

        $this->assertNotNull($tag1Id);
        $this->assertNotNull($tag2Id);

        $this->session->refresh(false);
        $this->liveSession->refresh(false);

        foreach ([$this->session, $this->liveSession] as $session) {
            $node = $session->getNode('/cmf/sulu_io/contents/english-page');

            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:en-excerpt-tags'));
            $this->assertSame([$tag1Id, $tag2Id], $this->nodeGetArrayValue($node, 'i18n:en-excerpt-tags'));
            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:de-excerpt-tags'));
            $this->assertSame([$tag1Id, $tag2Id], $this->nodeGetArrayValue($node, 'i18n:de-excerpt-tags'));

            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:en-excerpt-categories'));
            $this->assertSame([42, 43], $this->nodeGetArrayValue($node, 'i18n:en-excerpt-categories'));
            $this->assertCount(2, $this->nodeGetArrayValue($node, 'i18n:de-excerpt-categories'));
            $this->assertSame([42, 43], $this->nodeGetArrayValue($node, 'i18n:de-excerpt-categories'));

            $this->assertCount(1, $this->nodeGetArrayValue($node, 'i18n:en-navContexts'));
            $this->assertEquals(['main'], $this->nodeGetArrayValue($node, 'i18n:en-navContexts'));
            $this->assertCount(1, $this->nodeGetArrayValue($node, 'i18n:de-navContexts'));
            $this->assertEquals(['main'], $this->nodeGetArrayValue($node, 'i18n:de-navContexts'));

            $this->assertSame(12, (int) $this->nodeGetIntValue($node, 'i18n:en-author'));
            $this->assertSame(12, (int) $this->nodeGetIntValue($node, 'i18n:de-author'));

            $this->assertSame('2016-01-01T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:en-authored')?->format('c'));
            $this->assertSame('2016-01-01T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:de-authored')?->format('c'));

            $this->assertSame('2016-01-02T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:en-lastModified')?->format('c'));
            $this->assertSame('2016-01-02T00:00:00+00:00', $this->nodeGetDateTimeValue($node, 'i18n:de-lastModified')?->format('c'));

            $this->assertSame('default', $this->nodeGetStringValue($node, 'i18n:en-template'));
            $this->assertSame('default', $this->nodeGetStringValue($node, 'i18n:de-template'));
        }
    }

    private function nodeGetDateTimeValue(NodeInterface $node, string $propertyName): ?\DateTimeInterface
    {
        /** @var \DateTimeInterface|null $value */
        $value = $node->getPropertyValueWithDefault($propertyName, null);

        return $value;
    }

    private function nodeGetIntValue(NodeInterface $node, string $propertyName): ?int
    {
        /** @var int|null $value */
        $value = $node->getPropertyValueWithDefault($propertyName, null);

        return $value;
    }

    private function nodeGetStringValue(NodeInterface $node, string $propertyName): ?string
    {
        /** @var string|null $value */
        $value = $node->getPropertyValueWithDefault($propertyName, null);

        return $value;
    }

    /**
     * @return mixed[]
     */
    private function nodeGetArrayValue(NodeInterface $node, string $propertyName): array
    {
        /** @var mixed[] $value
         */
        $value = $node->getPropertyValueWithDefault($propertyName, []);

        return $value;
    }
}
