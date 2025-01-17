<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;

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

    /**
     * Invokes the callback if there are any listeners for the given event.
     * The invoked callback must return an instance of the given event class.
     * The returned event will be emitted to all the listeners.
     *
     * This method is useful when creating an event instance is costly and
     * instantiation should happen only if there are listeners.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(): TEvent $callback
     * @return void
     */
    public function emitIfListening(string $name, Closure $callback): void;
}
