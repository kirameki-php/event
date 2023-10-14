<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Core\Event;
use Kirameki\Core\EventHandler;
use Kirameki\Core\Exceptions\LogicException;
use ReflectionFunction;
use ReflectionNamedType;
use function is_a;

class EventEmitter
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
     * @return void
     */
    public function on(Closure $callback, bool $once = false): void
    {
        $this->append($this->extractEventName($callback), $callback, $once);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * Listener will be removed after it's called once.
     *
     * @template TEvent of Event
     * @param Closure(TEvent): mixed $callback
     * @return void
     */
    public function once(Closure $callback): void
    {
        $this->append($this->extractEventName($callback), $callback, true);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     * [Optional] Whether the listener should be removed after it's called once.
     * @return void
     */
    public function append(string $name, Closure $callback, bool $once = false): void
    {
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            $handler = $this->handlers[$name] = new EventHandler($name);
        }
        $handler->append($callback, $once);
    }

    /**
     * Prepends a listener to the beginning of the list for the given event.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     * [Optional] Whether the listener should be removed after it's called once.
     * @return void
     */
    public function prepend(string $name, Closure $callback, bool $once = false): void
    {
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            $handler = $this->handlers[$name] = new EventHandler($name);
        }
        $handler->prepend($callback, $once);
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

        foreach ($this->onEmittedCallbacks as $dispatchedCallback) {
            $dispatchedCallback($event, $count);
        }
    }

    /**
     * Invokes the callback if there are any listeners for the given event.
     * The invoked callback must return an instance of the given event class.
     * The returned event will be dispatched to all the listeners.
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
            throw new LogicException('$event must be an instance of '.$name, [
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
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return int<0, max>
     */
    public function removeListener(string $name, Closure $callback): int
    {
        $count = 0;

        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            return $count;
        }

        $count += $handler->removeListener($callback);

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

    /**
     * Extracts the event class name from the given callback.
     * The callback must have an Event as the first parameter.
     *
     * @template TEvent of Event
     * @param Closure(TEvent): mixed $callback
     * @return class-string<TEvent>
     */
    protected function extractEventName(Closure $callback): string
    {
        $type = (new ReflectionFunction($callback))
            ->getParameters()[0]
            ->getType();

        $name = ($type instanceof ReflectionNamedType)
            ? $type->getName()
            : '';

        if (!is_a($name, Event::class, true)) {
            throw new LogicException('The first parameter of the callback must be an instance of Event.', [
                'callback' => $callback,
            ]);
        }

        /** @var class-string<TEvent> */
        return $name;
    }
}
