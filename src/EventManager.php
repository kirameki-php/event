<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Kirameki\Event\Listeners\EventListener;
use function is_a;

class EventManager
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
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     * [Optional] Whether the listener should be removed after it's called once.
     * @return ($once is true ? CallbackOnceListener<TEvent> : CallbackListener<TEvent>)
     */
    public function on(Closure $callback, bool $once = false): CallbackListener
    {
        $listener = $once
            ? new CallbackOnceListener($callback)
            : new CallbackListener($callback);
        $this->append($listener);
        return $listener;
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter. Listener will be
     * removed after it's called once.
     *
     * @template TEvent of Event
     * @param Closure(TEvent): mixed $callback
     * @return CallbackOnceListener<TEvent>
     */
    public function once(Closure $callback): CallbackOnceListener
    {
        return $this->on($callback, true);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @param EventListener<TEvent> $listener
     * @return void
     */
    public function append(EventListener $listener): void
    {
        $name = $listener->getEventClass();
        $handler = $this->getHandlerOrNull($name);
        $handler ??= $this->handlers[$name] = new EventHandler($name);
        $handler->append($listener);
    }

    /**
     * Prepends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @param EventListener<TEvent> $listener
     * @return void
     */
    public function prepend(EventListener $listener): void
    {
        $name = $listener->getEventClass();
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            $handler = $this->handlers[$name] = new EventHandler($name);
        }
        $handler->prepend($listener);
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
     * Calls all listeners for the given event.
     *
     * @template TEvent of Event
     * @param TEvent $event
     * @return void
     */
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
    public function emitIfListening(string $name, Closure $callback): void
    {
        if (!$this->hasListeners($name)) {
            return;
        }

        $event = $callback();
        if (!is_a($event, $name)) {
            throw new LogicException('$event must be an instance of ' . $name, [
                'event' => $event,
                'class' => $name,
            ]);
        }

        $this->emit($event);
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

        $name = $listener->getEventClass();
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            return $count;
        }

        $count += $handler->removeListener($listener);

        if (!$handler->hasListeners()) {
            unset($this->handlers[$name]);
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
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>|null
     */
    protected function getHandlerOrNull(string $name): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->handlers[$name] ?? null;
    }
}
