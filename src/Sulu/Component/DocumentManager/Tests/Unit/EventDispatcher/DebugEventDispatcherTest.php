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

namespace Sulu\Component\DocumentManager\Tests\Unit\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Sulu\Component\DocumentManager\Event\AbstractDocumentEvent;
use Sulu\Component\DocumentManager\EventDispatcher\DebugEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

final class DebugEventDispatcherTest extends TestCase
{
    public const TEST = 'test';

    /**
     * @var DebugEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var AbstractLogger
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->logger = new class extends AbstractLogger {
            /**
             * @var array<string|\Stringable>
             */
            private $logOutput = [];

            /**
             * @param array<mixed> $context
             * @param string|\Stringable $message
             */
            public function log($level, $message, array $context = []): void
            {
                $this->logOutput[] = $message;
            }

            /**
             * @return array<string|\Stringable>
             */
            public function getLogOutput(): array
            {
                return $this->logOutput;
            }

            /**
             * @param object $event
             * @param string $eventName
             * @param mixed $ref
             */
            public function listenerMock($event, $eventName, $ref): void
            {
            }
        };

        $this->dispatcher = new DebugEventDispatcher(new Stopwatch(), $this->logger);
        $this->dispatcher->addListener(self::TEST, [$this->logger, 'listenerMock']);
    }

    public function testDebugLogWritten(): void
    {
        $this->dispatcher->dispatch(new class extends AbstractDocumentEvent {
            public function __construct()
            {
                parent::__construct(new \stdClass());
            }
        }, self::TEST);
        $this->assertTrue(\method_exists($this->logger, 'getLogOutput'));
        $this->assertCount(1, $this->logger->getLogOutput());
    }
}
