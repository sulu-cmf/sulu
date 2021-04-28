<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Analytics;

use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecord;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Tests\Functional\BaseFunctional;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AnalyticsManagerTest extends BaseFunctional
{
    /**
     * @var AnalyticsInterface[]
     */
    private $entities = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->initEntities();
    }

    public function initEntities()
    {
        $this->entities[] = $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [
                    ['url' => 'www.sulu.io/{localization}', 'environment' => 'test'],
                ],
            ]
        );
        $this->entities[] = $this->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'piwik',
                'content' => '123',
                'domains' => [
                    ['url' => 'www.test.io', 'environment' => 'prod'],
                    ['url' => '{country}.test.io', 'environment' => 'test'],
                ],
            ]
        );
        $this->entities[] = $this->create(
            'sulu_io',
            [
                'title' => 'test-3',
                'type' => 'custom',
                'content' => '<div/>',
                'domains' => [
                    ['url' => 'www.google.at', 'environment' => 'stage'],
                    ['url' => '{localization}.google.at', 'environment' => 'test'],
                ],
            ]
        );
        $this->entities[] = $this->create(
            'sulu_io',
            [
                'title' => 'test-4',
                'type' => 'google_tag_manager',
                'content' => 'GTM-XXXX',
                'domains' => [['url' => 'www.sulu.io', 'environment' => 'test']],
            ]
        );
        $this->entities[] = $this->create(
            'test_io',
            [
                'title' => 'test piwik',
                'type' => 'piwik',
                'content' => '123',
                'domains' => [
                    ['url' => 'www.test.io', 'environment' => 'prod'],
                    ['url' => '{country}.test.io', 'environment' => 'test'],
                ],
            ]
        );
    }

    public function testFindAll()
    {
        $result = $this->analyticsManager->findAll('sulu_io');
        $this->assertCount(4, $result);
        $this->assertEquals('test-1', $result[0]->getTitle());
        $this->assertEquals('test-2', $result[1]->getTitle());
        $this->assertEquals('test-3', $result[2]->getTitle());
        $this->assertEquals('test-4', $result[3]->getTitle());

        $result = $this->analyticsManager->findAll('test_io');
        $this->assertCount(1, $result);
        $this->assertEquals('test piwik', $result[0]->getTitle());

        $result = $this->analyticsManager->findAll('test');
        $this->assertEmpty($result);
    }

    public function testFind()
    {
        $id = $this->entities[0]->getId();
        $this->assertNotNull($id);
        $result = $this->analyticsManager->find($id);

        $this->assertEquals('test-1', $result->getTitle());
        $this->assertEquals('google', $result->getType());
        $this->assertEquals('UA123-123', $result->getContent());

        $domainCollection = $result->getDomains();
        $this->assertNotNull($domainCollection);
        $domains = $domainCollection->getValues();

        $this->assertCount(1, $domains);
        $this->assertEquals('www.sulu.io/{localization}', $domains[0]);
    }

    public function dataProvider()
    {
        return [
            [
                'sulu_io',
                [
                    'title' => 'test-1',
                    'type' => 'google',
                    'content' => 'test-1',
                    'allDomains' => true,
                    'domains' => null,
                ],
            ],
            [
                'test_io',
                [
                    'title' => 'test-1',
                    'type' => 'google',
                    'content' => 'test-1',
                    'allDomains' => true,
                ],
            ],
            [
                'test_io',
                [
                    'title' => 'test-1',
                    'type' => 'google',
                    'domains' => ['www.sulu.io'],
                ],
            ],
            [
                'test_io',
                [
                    'title' => 'test-1',
                    'type' => 'google',
                    'domains' => ['www.sulu.io', 'www.sulu.io/{localization}'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreate($webspaceKey, array $data)
    {
        $result = $this->analyticsManager->create($webspaceKey, $data);
        $this->getEntityManager()->flush();

        $eventLogRepository = $this->getEventLogRepository();

        /** @var EventRecord[] $events */
        $events = $eventLogRepository->findAll();
        $this->assertCount(1, $events);
        $this->assertSame((string) $result->getId(), $events[0]->getResourceId());
        $this->assertSame('created', $events[0]->getEventType());

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $key => $value) {
            if ('domains' === $key) {
                continue;
            }
            $this->assertEquals($value, $accessor->getValue($result, $key));
        }

        $domainCollection = $result->getDomains();
        if ($domainCollection) {
            $domains = $domainCollection->getValues();
            for ($i = 0; $i < \count($domains); ++$i) {
                $this->assertEquals($data['domains'][0], $domains[0]);
            }
        }

        $this->assertCount(
            1,
            \array_filter(
                $this->analyticsManager->findAll($webspaceKey),
                function(AnalyticsInterface $analytics) use ($result) {
                    return $analytics->getId() === $result->getId();
                }
            )
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdate($webspaceKey, array $data)
    {
        $id = $this->entities[0]->getId();
        $this->assertNotNull($id);

        $result = $this->analyticsManager->update($id, $data);
        $this->getEntityManager()->flush();

        $eventLogRepository = $this->getEventLogRepository();

        /** @var EventRecord[] $events */
        $events = $eventLogRepository->findAll();
        $this->assertCount(1, $events);
        $this->assertSame((string) $result->getId(), $events[0]->getResourceId());
        $this->assertSame('modified', $events[0]->getEventType());

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $key => $value) {
            if ('domains' === $key) {
                continue;
            }
            $this->assertEquals($value, $accessor->getValue($result, $key));
        }

        $domains = $result->getDomains();
        if ($domains) {
            $domains = \array_values($domains->toArray());
            $this->assertCount(\count($data['domains']), $domains);
            for ($i = 0; $i < \count($domains); ++$i) {
                $this->assertContains($domains[$i], $data['domains']);
            }
        }

        $this->assertCount(
            1,
            \array_filter(
                $this->analyticsManager->findAll($result->getWebspaceKey()),
                function(AnalyticsInterface $analytics) use ($result) {
                    return $analytics->getTitle() === $result->getTitle();
                }
            )
        );
    }

    public function testCreateWithExistingUrl()
    {
        $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'domains' => [
                    'www.sulu.at',
                    'www.sulu.io/{localization}',
                ],
            ]
        );
        $this->getEntityManager()->flush();

        $this->assertCount(1, $this->domainRepository->findBy(['url' => 'www.sulu.at', 'environment' => 'test']));
        $this->assertCount(
            1,
            $this->domainRepository->findBy(['url' => 'www.sulu.io/{localization}', 'environment' => 'test'])
        );
        $this->assertCount(1, $this->domainRepository->findBy(['url' => 'www.sulu.io/{localization}']));
    }

    public function testUpdateWithExistingUrl()
    {
        $id = $this->entities[0]->getId();
        $this->assertNotNull($id);

        $this->analyticsManager->update(
            $id,
            [
                'title' => 'test-1',
                'type' => 'google',
                'domains' => ['www.sulu.at', 'www.sulu.io/{localization}'],
            ]
        );
        $this->getEntityManager()->flush();

        $this->assertCount(1, $this->domainRepository->findBy(['url' => 'www.sulu.at', 'environment' => 'test']));
        $this->assertCount(
            1,
            $this->domainRepository->findBy(['url' => 'www.sulu.io/{localization}', 'environment' => 'test'])
        );
        $this->assertCount(1, $this->domainRepository->findBy(['url' => 'www.sulu.io/{localization}']));
    }

    public function testRemove()
    {
        $id = $this->entities[0]->getId();
        $this->assertNotNull($id);

        $this->analyticsManager->remove($id);
        $this->getEntityManager()->flush();

        $eventLogRepository = $this->getEventLogRepository();

        /** @var EventRecord[] $events */
        $events = $eventLogRepository->findAll();
        $this->assertCount(1, $events);
        $this->assertSame((string) $id, $events[0]->getResourceId());
        $this->assertSame('removed', $events[0]->getEventType());

        $this->assertEmpty(
            \array_filter(
                $this->analyticsManager->findAll('sulu_io'),
                function(AnalyticsInterface $analytics) {
                    return $analytics->getId() === $this->entities[0]->getId();
                }
            )
        );
    }

    public function testRemoveMultiple()
    {
        $id1 = $this->entities[0]->getId();
        $this->assertNotNull($id1);

        $id2 = $this->entities[1]->getId();
        $this->assertNotNull($id2);

        $ids = [$id1, $id2];
        $this->analyticsManager->removeMultiple($ids);
        $this->getEntityManager()->flush();

        $this->assertEmpty(
            \array_filter(
                $this->analyticsManager->findAll('sulu_io'),
                function(AnalyticsInterface $analytics) use ($ids) {
                    return \in_array($analytics->getId(), $ids);
                }
            )
        );
    }
}
