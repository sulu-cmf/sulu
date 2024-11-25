<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Traits;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

trait ProfilerHelperTrait
{
    public static function getDbDataCollector(bool $reset = false): DoctrineDataCollector
    {
        $profiler = static::getContainer()->get('profiler');
        $profiler->enable();

        /** @var DoctrineDataCollector $dbDataCollector */
        $dbDataCollector = $profiler->get('db');

        if ($reset) {
            $dbDataCollector->reset();
        }

        return $dbDataCollector;
    }

    public static function collectDataCollector(DataCollectorInterface $dataCollector): void
    {
        // this is a hack to use the data collector in a none request context
        $dataCollector->collect(new Request(), new Response());
    }
}
