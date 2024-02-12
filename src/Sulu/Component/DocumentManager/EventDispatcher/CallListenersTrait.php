<?php

namespace Sulu\Component\DocumentManager\EventDispatcher;

if (\class_exists( 'Symfony\Component\EventDispatcher\Event')) { // indicates its Symfony 4.4 or lower
    /**
     * @internal
     */
    trait CallListenersTrait
    {
        protected function callListeners(iterable $listeners, string $eventName, $event)
        {
            $this->doCallListeners($listeners, $eventName, $event);
        }
    }
} else {
    /**
     * @internal
     */
    trait CallListenersTrait
    {
        protected function callListeners(iterable $listeners, string $eventName, object $event): void
        {
            $this->doCallListeners($listeners, $eventName, $event);
        }
    }
}
