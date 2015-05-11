<?php

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\Table;
use Sulu\Component\DocumentManager\Events;

class SubscriberDebugCommand extends ContainerAwareCommand
{
    const PREFIX = 'sulu_document_manager.';

    public function configure()
    {
        $this->setName('sulu:document:subscriber:debug');
        $this->addArgument('event_name', InputArgument::OPTIONAL, 'Event name, without the sulu_document_manager. prefix');
        $this->setDescription('Show event listeners associated with the document manager');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $eventName = $input->getArgument('event_name');
        $dispatcher = $this->getContainer()->get('sulu_document_manager.event_dispatcher');

        if (!$eventName) {
            return $this->showEventNames($output);
        }

        $eventName = self::PREFIX . $eventName;
        $listeners = $dispatcher->getListeners($eventName);

        foreach ($listeners as $listenerTuple) {
            list($listener, $methodName) = $listenerTuple;
            $refl = new \ReflectionClass(get_class($listener));
            $priority = $this->getPriority($eventName, $methodName, $listener);
            $rows[] = array(
                sprintf(
                    '<comment>%s</comment>\\%s',
                    $refl->getNamespaceName(),
                    $refl->getShortName()
                ),
                $methodName,
                $priority
            );
        }

        usort($rows, function ($a, $b) {
            return $a[2] < $b[2];
        });

        $table = new Table($output);
        $table->setHeaders(array('Class', 'Method', 'Priority'));
        $table->setRows($rows);
        $table->render();
    }

    private function getPriority($eventName, $methodName, $listener)
    {
        $events = $listener::getSubscribedEvents();
        $events = $events[$eventName];

        if (is_string($events)) {
            return 0;
        }

        return $this->resolvePriority($events, $methodName);
    }

    private function resolvePriority($value, $targetMethodName)
    {
        if (count($value) == 1) {
            return 0;
        }

        list($methodName, $priority) = $value;

        if (is_string($methodName) && is_numeric($priority)) {
            if ($methodName === $targetMethodName) {
                return $priority;
            }

            return null;
        }

        foreach ($value as $event) {
            $resolved = $this->resolvePriority($event, $targetMethodName);
            if (null !== $resolved) {
                return $resolved;
            }
        }
    }

    private function showEventNames(OutputInterface $output)
    {
        $refl = new \ReflectionClass(Events::class);
        $constants = $refl->getConstants();
        $output->writeln('Specify one of the following event names to display the subscribers:');

        $table = new Table($output);

        $table->setHeaders(array('Event'));
        foreach ($constants as $name => $value) {
            $table->addRow(array(
                substr($value, strlen(self::PREFIX))
            ));
        }
        $table->render();
    }
}
