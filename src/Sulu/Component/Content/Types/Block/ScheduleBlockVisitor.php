<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Block;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestEnhancer;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class ScheduleBlockVisitor implements BlockVisitorInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var CacheLifetimeRequestEnhancer
     */
    private $cacheLifetimeRequestEnhancer;

    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        CacheLifetimeRequestEnhancer $cacheLifetimeRequestEnhancer
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->cacheLifetimeRequestEnhancer = $cacheLifetimeRequestEnhancer;
    }

    public function visit(BlockPropertyType $block): ?BlockPropertyType
    {
        $blockPropertyTypeSettings = $block->getSettings();

        if (!\is_array($blockPropertyTypeSettings)
            || !isset($blockPropertyTypeSettings['schedules_enabled'])
            || !$blockPropertyTypeSettings['schedules_enabled']
            || !isset($blockPropertyTypeSettings['schedules'])
            || !\is_array($blockPropertyTypeSettings['schedules'])
        ) {
            return $block;
        }

        $now = $this->requestAnalyzer->getDateTime();
        $nowTimestamp = $now->getTimestamp();

        $returnBlock = false;

        foreach ($blockPropertyTypeSettings['schedules'] as $schedule) {
            switch ($schedule['type']) {
                case 'fixed':
                    $start = new \DateTime($schedule['start']);
                    $end = new \DateTime($schedule['end']);

                    $this->cacheLifetimeRequestEnhancer->setCacheLifetime($start->getTimestamp() - $nowTimestamp);
                    $this->cacheLifetimeRequestEnhancer->setCacheLifetime($end->getTimestamp() - $nowTimestamp);

                    if ($now >= $start && $now <= $end) {
                        $returnBlock = true;
                        continue 2;
                    }
                    break;
                case 'weekly':
                    $year = $now->format('Y');
                    $month = $now->format('m');
                    $day = $now->format('d');

                    $start = new \DateTime($schedule['start']);
                    $start->setDate($year, $month, $day);
                    $end = new \DateTime($schedule['end']);
                    $end->setDate($year, $month, $day);

                    if ($end < $start) {
                        $end->modify('+1 day');

                        if (!$this->matchWeekday($start, $schedule)) {
                            $start->modify('-1 day');
                            $end->modify('-1 day');
                        }

                        if (!$this->matchWeekday($start, $schedule)) {
                            continue 2;
                        }
                    } else {
                        if (!$this->matchWeekday($start, $schedule)) {
                            continue 2;
                        }
                    }

                    if ($now >= $start && $now <= $end) {
                        $returnBlock = true;
                    }
                    break;
            }
        }

        return $returnBlock ? $block : null;
    }

    private function matchWeekday(\DateTime $datetime, $schedule)
    {
        if (!\is_array($schedule['days'])) {
            return true;
        }

        return \in_array(\strtolower($datetime->format('l')), $schedule['days']);
    }
}
