<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Kirameki\Event\Listeners\EventListener;
use Override;

class EventManager implements EventEmitter
{
    /**
     * @var array<class-string, EventHandler<Event>>
     */
    protected array $handlers = [];

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
     * @param class-string<TEvent> $class
     * @param Closure(TEvent): mixed $callback
     * @return CallbackListener<TEvent>
     */
    public function on(string $class, Closure $callback): CallbackListener
    {
        return $this->append(new CallbackListener($callback, $class));
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter. Listener will be
     * removed after it's called once.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $class
     * @param Closure(TEvent): mixed $callback
     * @return CallbackOnceListener<TEvent>
     */
    public function once(string $class, Closure $callback): CallbackOnceListener
    {
        return $this->append(new CallbackOnceListener($callback, $class));
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
        return $this->resolveHandler($listener)->append($listener);
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
        return $this->resolveHandler($listener)->prepend($listener);
    }

    /**
     * Checks if there are any listeners for the given event.
     *
     * @param class-string<Event> $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return array_key_exists($name, $this->handlers);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function emit(Event $event): void
    {
        $count = 0;
        if ($handler = $this->getHandlerOrNull($event::class)) {
            $count = $handler->emit($event);

            if (!$handler->hasListeners()) {
                unset($this->handlers[$event::class]);
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
        $handler = $this->getHandlerOrNull($class);
        if ($handler === null) {
            return $count;
        }

        $count += $handler->removeListener($listener);

        if (!$handler->hasListeners()) {
            unset($this->handlers[$class]);
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
        if ($this->hasListeners($name)) {
            unset($this->handlers[$name]);
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

    /**
     * Get the handler for the given event.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $class
     * @return EventHandler<TEvent>|null
     */
    protected function getHandlerOrNull(string $class): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->handlers[$class] ?? null;
    }

    /**
     * @template TEvent of Event
     * @param EventListener<TEvent> $listener
     * @return EventHandler<TEvent>
     */
    protected function resolveHandler(EventListener $listener): EventHandler
    {
        $class = $listener->getEventClass();
        $handler = $this->getHandlerOrNull($class);
        $handler ??= $this->handlers[$class] = new EventHandler($class);
        return $handler;
    }
}
