<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types\Block;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Types\Block\ScheduleBlockVisitor;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class ScheduleBlockVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<CacheLifetimeRequestStore>
     */
    private $cacheLifetimeRequestStore;

    /**
     * @var ScheduleBlockVisitor
     */
    private $scheduleBlockVisitor;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->cacheLifetimeRequestStore = $this->prophesize(CacheLifetimeRequestStore::class);
        $this->scheduleBlockVisitor = new ScheduleBlockVisitor(
            $this->requestAnalyzer->reveal(),
            $this->cacheLifetimeRequestStore->reveal()
        );
    }

    public function testShouldNotSkipWithObjectAsSettings(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertEquals($blockPropertyType, $this->scheduleBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertEquals($blockPropertyType, $this->scheduleBlockVisitor->visit($blockPropertyType));
    }

    public static function provideVisit()
    {
        return [
            [
                [
                    'schedules_enabled' => true,
                ],
                '2020-11-19T08:00:00',
                false,
                [],
            ],
            [
                [
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [],
            ],
            [
                [
                    'schedules' => [
                        ['type' => 'fixed'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [],
            ],
            [
                [
                    'schedules_enabled' => false,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => new \stdClass(),
                ],
                '2020-11-19T08:00:00',
                false,
                [],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [-345600],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00'],
                    ],
                ],
                '2020-11-14T08:00:00',
                true,
                [86400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'end' => '2020-11-20T08:00:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [86400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'end' => '2020-11-20T08:00:00'],
                    ],
                ],
                '2020-11-21T08:00:00',
                true,
                [-86400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                true,
                [-345600, -50400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-16T08:00:00',
                false,
                [-86400, 208800],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                        ['type' => 'fixed', 'start' => '2020-11-20T08:00:00', 'end' => '2020-11-25T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                true,
                [-345600, -50400, 86400, 554400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-19T18:00'],
                        ['type' => 'fixed', 'start' => '2020-11-20T08:00:00', 'end' => '2020-11-25T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
                [-345600, 36000, 86400, 554400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['monday', 'tuesday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T10:00:00', // Thursday
                true,
                [338400, 352800],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T10:00:00', // Thursday
                false,
                [-7200, 7200],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                true,
                [-43200, -28800],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '20:00:00',
                            'end' => '05:00:00',
                        ],
                    ],
                ],
                '2020-11-19T22:00:00', // Thursday
                false,
                [-7200, 25200],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '20:00:00',
                            'end' => '05:00:00',
                        ],
                    ],
                ],
                '2020-11-20T01:00:00', // Friday morning
                false,
                [-18000, 14400],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '20:00:00',
                            'end' => '05:00:00',
                        ],
                    ],
                ],
                '2020-11-20T07:00:00', // Friday morning
                true,
                [-39600, -7200],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '20:00:00',
                            'end' => '05:00:00',
                        ],
                    ],
                ],
                '2020-11-19T19:00:00', // Thursday
                true,
                [3600, 36000],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '14:00:00',
                            'end' => '21:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
                [-43200, -28800, -21600, 3600],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '23:00:00',
                        ],
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '14:00:00',
                            'end' => '21:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
                [-43200, 10800, -21600, 3600],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                true,
                [-43200, -28800, -388800, -93600],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '20:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
                [-43200, 0, -388800, -93600],
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-19T08:00:00', 'end' => '2020-11-20T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
                [-43200, -28800, -43200, 79200],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideVisit')]
    public function testVisit($settings, $now, $skip, $requestCacheLifetimes): void
    {
        $nowDateTime = new \DateTime($now);
        $this->requestAnalyzer->getDateTime()->willReturn($nowDateTime);

        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings($settings);

        if (false === $skip) {
            $this->assertEquals($blockPropertyType, $this->scheduleBlockVisitor->visit($blockPropertyType));
        } else {
            $this->assertNull($this->scheduleBlockVisitor->visit($blockPropertyType));
        }

        foreach ($requestCacheLifetimes as $cacheLifetime) {
            $this->cacheLifetimeRequestStore->setCacheLifetime($cacheLifetime)->shouldBeCalled();
        }
    }
}
