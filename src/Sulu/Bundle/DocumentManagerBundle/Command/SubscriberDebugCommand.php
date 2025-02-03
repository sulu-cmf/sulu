<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'sulu:document:subscriber:debug', description: 'Show event listeners associated with the document manager')]
class SubscriberDebugCommand extends Command
{
    public const PREFIX = 'sulu_document_manager.';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('event_name', InputArgument::OPTIONAL, 'Event name, without the sulu_document_manager. prefix');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventName = $input->getArgument('event_name');

        if (!$eventName) {
            return $this->showEventNames($output);
        }

        $eventName = self::PREFIX . $eventName;
        $listeners = $this->eventDispatcher->getListeners($eventName);

        $rows = [];

        foreach ($listeners as $listenerTuple) {
            list($listener, $methodName) = $listenerTuple;
            $refl = new \ReflectionClass(\get_class($listener));
            $priority = $this->getPriority($eventName, $methodName, $listener);
            $rows[] = [
                \sprintf(
                    '<comment>%s</comment>\\%s',
                    $refl->getNamespaceName(),
                    $refl->getShortName()
                ),
                $methodName,
                $priority,
            ];
        }

        \usort($rows, function($a, $b) {
            return $b[2] <=> $a[2];
        });

        $table = new Table($output);
        $table->setHeaders(['Class', 'Method', 'Priority']);
        $table->setRows($rows);
        $table->render();

        return 0;
    }

    private function getPriority($eventName, $methodName, $listener)
    {
        $events = $listener::getSubscribedEvents();
        $events = $events[$eventName];

        if (\is_string($events)) {
            return 0;
        }

        return $this->resolvePriority($events, $methodName);
    }

    private function resolvePriority($value, $targetMethodName)
    {
        if (1 == \count($value)) {
            return 0;
        }

        list($methodName, $priority) = $value;

        if (\is_string($methodName) && \is_numeric($priority)) {
            if ($methodName === $targetMethodName) {
                return $priority;
            }

            return;
        }

        foreach ($value as $event) {
            $resolved = $this->resolvePriority($event, $targetMethodName);
            if (null !== $resolved) {
                return $resolved;
            }
        }
    }

    private function showEventNames(OutputInterface $output): int
    {
        $refl = new \ReflectionClass(Events::class);
        $constants = $refl->getConstants();
        $output->writeln('Specify one of the following event names to display the subscribers:');

        $table = new Table($output);

        $table->setHeaders(['Event']);
        foreach ($constants as $name => $value) {
            $table->addRow([
                \substr($value, \strlen(self::PREFIX)),
            ]);
        }
        $table->render();

        return 0;
    }
}
