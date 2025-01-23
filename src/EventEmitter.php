<?php declare(strict_types=1);

namespace Kirameki\Event;

interface EventEmitter
{
    /**
     * Calls all listeners for the given event.
     *
     * @template TEvent of Event
     * @param TEvent $event
     * @return void
     */
    public function emit(Event $event): void;
}
