<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Kirameki\Event\Listeners\EventListener;
use Override;

class EventManager implements EventEmitter
{
    use HandlesEvents;

    /**
     * @var list<Closure(Event, int): mixed>
     */
    protected array $onEmittedCallbacks = [];

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter.
     *
     * This method is useful and cleaner than using append() but is slower since
     * it needs to extract the event class name from the callback using reflections.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return CallbackListener<TEvent>
     */
    public function on(string $name, Closure $callback): CallbackListener
    {
        return $this->resolveEventHandler($name)->do($callback);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter. Listener will be
     * removed after it's called once.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return CallbackOnceListener<TEvent>
     */
    public function once(string $name, Closure $callback): CallbackOnceListener
    {
        return $this->resolveEventHandler($name)->doOnce($callback);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @template TListener of EventListener<TEvent>
     * @param TListener $listener
     * @return TListener
     */
    public function append(EventListener $listener): EventListener
    {
        return $this->resolveEventHandler($listener->getEventClass())->append($listener);
    }

    /**
     * Prepends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @template TListener of EventListener<TEvent>
     * @param TListener $listener
     * @return TListener
     */
    public function prepend(EventListener $listener): EventListener
    {
        return $this->resolveEventHandler($listener->getEventClass())->prepend($listener);
    }

    /**
     * Checks if there are any listeners for the given event.
     *
     * @param class-string<Event> $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return $this->eventHasListeners($name);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function emit(Event $event, bool &$wasCanceled = false): void
    {
        $count = 0;
        if ($handler = $this->getEventHandlerOrNull($event::class)) {
            $count = $handler->emit($event, $wasCanceled);

            if (!$handler->hasListeners()) {
                $this->removeEventHandler($event::class);
            }
        }

        foreach ($this->onEmittedCallbacks as $callback) {
            $callback($event, $count);
        }
    }

    /**
     * Removes the given callback from the listeners of the given event.
     * Returns the number of listeners that were removed.
     *
     * @template TEvent of Event
     * @param EventListener<TEvent> $listener
     * @return int<0, max>
     */
    public function removeListener(EventListener $listener): int
    {
        $count = 0;

        $class = $listener->getEventClass();
        $handler = $this->getEventHandlerOrNull($class);
        if ($handler === null) {
            return $count;
        }

        $count += $handler->removeListener($listener);

        if (!$handler->hasListeners()) {
            $this->removeEventHandler($class);
        }

        return $count;
    }

    /**
     * Remove all listeners for the given event.
     *
     * @param class-string<Event> $name
     * @return bool
     */
    public function removeAllListeners(string $name): bool
    {
        if ($this->eventHasListeners($name)) {
            $this->removeEventHandler($name);
            return true;
        }
        return false;
    }

    /**
     * Registers a callback that will be invoked whenever an event is emitted.
     *
     * @param Closure(Event, int): mixed $callback
     * @return void
     */
    public function onEmitted(Closure $callback): void
    {
        $this->onEmittedCallbacks[] = $callback;
    }
}
