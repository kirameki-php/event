<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Core\Event;
use Kirameki\Core\EventHandler;
use Kirameki\Core\Exceptions\LogicException;
use function is_a;

class EventDispatcher
{
    /**
     * @var array<class-string, EventHandler<Event>>
     */
    protected array $handlers = [];

    /**
     * @var list<Closure(Event, int): mixed>
     */
    protected array $dispatchedCallbacks = [];

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return void
     */
    public function listen(string $name, Closure $callback): void
    {
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            $handler = $this->handlers[$name] = new EventHandler($name);
        }
        $handler->listen($callback);
    }

    /*
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
    public function dispatch(Event $event): void
    {
        $count = 0;
        if ($handler = $this->getHandlerOrNull($event::class)) {
            $count = $handler->dispatch($event);

            if (!$handler->hasListeners()) {
                unset($this->handlers[$event::class]);
            }
        }

        foreach ($this->dispatchedCallbacks as $dispatchedCallback) {
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
    public function dispatchIfListening(string $name, Closure $callback): void
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

        $this->dispatch($event);
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
    public function removeListenersFor(string $name): bool
    {
        if ($this->hasListeners($name)) {
            unset($this->handlers[$name]);
            return true;
        }
        return false;
    }

    /**
     * Registers a callback that will be invoked whenever an event is dispatched.
     *
     * @param Closure(Event, int): mixed $callback
     * @return void
     */
    public function onDispatched(Closure $callback): void
    {
        $this->dispatchedCallbacks[] = $callback;
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
