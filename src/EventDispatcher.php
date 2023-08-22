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
    protected array $listenersByEvent = [];

    /**
     * @var list<Closure(class-string<Event>, Closure, bool): mixed>
     */
    protected array $addedCallbacks = [];

    /**
     * @var list<Closure(class-string<Event>, Closure): mixed>
     */
    protected array $removedCallbacks = [];

    /**
     * @var list<Closure(Event, int): mixed>
     */
    protected array $dispatchedCallbacks = [];

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     * @return void
     */
    public function listen(string $name, Closure $callback, bool $once = false): void
    {
        $handler = $this->getHandlerOrNull($name);
        if ($handler === null) {
            $handler = $this->listenersByEvent[$name] = new EventHandler($name);
        }
        $handler->listen($callback, $once);

        foreach ($this->addedCallbacks as $addedCallback) {
            $addedCallback($name, $callback, $once);
        }
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return void
     */
    public function listenOnce(string $name, Closure $callback): void
    {
        $this->listen($name, $callback, true);
    }

    /**
     * @param class-string<Event> $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return array_key_exists($name, $this->listenersByEvent);
    }

    /**
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
                unset($this->listenersByEvent[$event::class]);
            }
        }

        foreach ($this->dispatchedCallbacks as $dispatchedCallback) {
            $dispatchedCallback($event, $count);
        }
    }

    /**
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
            unset($this->listenersByEvent[$name]);
        }

        foreach ($this->removedCallbacks as $removedCallback) {
            $removedCallback($name, $callback);
        }

        return $count;
    }

    /**
     * @param class-string<Event> $name
     * @return bool
     */
    public function removeListenersFor(string $name): bool
    {
        if ($this->hasListeners($name)) {
            unset($this->listenersByEvent[$name]);
            return true;
        }
        return false;
    }

    /**
     * @param Closure(class-string<Event>, Closure, bool): mixed $callback
     * @return void
     */
    public function onListenerAdded(Closure $callback): void
    {
        $this->addedCallbacks[] = $callback;
    }

    /**
     * @param Closure(class-string<Event>, Closure): mixed $callback
     * @return void
     */
    public function onListenerRemoved(Closure $callback): void
    {
        $this->removedCallbacks[] = $callback;
    }

    /**
     * @param Closure(Event, int): mixed $callback
     * @return void
     */
    public function onDispatched(Closure $callback): void
    {
        $this->dispatchedCallbacks[] = $callback;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>|null
     */
    protected function getHandlerOrNull(string $name): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->listenersByEvent[$name] ?? null;
    }
}
