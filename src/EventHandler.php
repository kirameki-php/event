<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use LogicException;
use function class_parents;
use function count;
use function is_a;

class EventHandler
{
    /**
     * @var array<class-string, list<Listener<Event>>>
     */
    protected array $events = [];

    /**
     * @var list<Closure(class-string<Event>, Listener<Event>): mixed>
     */
    protected array $addedCallbacks = [];

    /**
     * @var list<Closure(class-string<Event>): mixed>
     */
    protected array $removedCallbacks = [];

    /**
     * @var list<Closure(Event): mixed>
     */
    protected array $dispatchedCallbacks = [];

    /**
     * @template TEvent of Event
     * @param class-string<Event> $name
     * @param Closure(TEvent, Listener<TEvent>): TEvent|Listener<TEvent> $callback
     * @param bool $once
     * @return Listener<TEvent>
     */
    public function listen(string $name, Closure|Listener $callback, bool $once = false): Listener
    {
        $listener = $callback instanceof Closure
            ? new Listener($callback, $once)
            : $callback;

        $this->events[$name] ??= [];
        $this->events[$name][] = $listener;

        $this->invokeCallbacks($this->addedCallbacks, $name, $callback, $once);

        return $listener;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent, Listener<TEvent>): TEvent|Listener<TEvent> $callback
     * @return Listener<TEvent>
     */
    public function listenOnce(string $name, Closure|Listener $callback): Listener
    {
        return $this->listen($name, $callback, true);
    }

    /**
     * @param class-string<Event> $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return isset($this->events[$name]);
    }

    /**
     * @template TEvent of Event
     * @param TEvent $event
     * @return void
     */
    public function dispatch(Event $event): void
    {
        $name = $event::class;

        foreach ($this->getClassHierarchy($name) as $hierarchy) {
            if (!$this->hasListeners($hierarchy)) {
                continue;
            }

            $listeners = $this->events[$hierarchy] ?? [];

            foreach ($listeners as $index => $listener) {
                $listener->invoke($event);

                if (!$listener->invokedOnlyOnce()) {
                    unset($listeners[$index]);
                }

                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        $this->invokeCallbacks($this->dispatchedCallbacks, $event);
    }

    /**
     * @param class-string<Event> $class
     * @param mixed ...$args
     * @return void
     */
    public function dispatchClass(string $class, ...$args): void
    {
        if ($this->hasListeners($class)) {
            $this->dispatch(new $class(...$args));
        }
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent, Listener<TEvent>): mixed|Listener<TEvent> $targetListener
     * @return void
     */
    public function removeListener(string $name, Closure|Listener $targetListener): void
    {
        if (!$this->hasListeners($name)) {
            return;
        }

        $target = $targetListener instanceof Listener
            ? $targetListener->getCallback()
            : $targetListener;

        $listeners = &$this->events[$name];
        foreach ($listeners as $index => $listener) {
            /** @var Closure(TEvent, Listener<TEvent>): mixed $callback */
            $callback = $listener->getCallback();
            if ($callback === $target) {
                unset($listeners[$index]);
            }
        }

        if (count($listeners) === 0) {
            unset($this->events[$name]);
        }

        $this->invokeCallbacks($this->removedCallbacks, $name, $targetListener);
    }

    /**
     * @param class-string<Event> $name
     * @return void
     */
    public function removeAllListeners(string $name): void
    {
        unset($this->events[$name]);
        $this->invokeCallbacks($this->removedCallbacks, $name, null);
    }

    /**
     * @param Closure(class-string<Event>, Listener<Event>): mixed $callback
     * @return void
     */
    public function onListenerAdded(Closure $callback): void
    {
        $this->addedCallbacks[] = $callback;
    }

    /**
     * @param Closure(class-string<Event>): mixed $callback
     * @return void
     */
    public function onListenerRemoved(Closure $callback): void
    {
        $this->removedCallbacks[] = $callback;
    }

    /**
     * @param Closure(Event): mixed $callback
     * @return void
     */
    public function onDispatched(Closure $callback): void
    {
        $this->dispatchedCallbacks[] = $callback;
    }

    /**
     * @param class-string<Event> $name
     * @return array<class-string<Event>>
     */
    protected function getClassHierarchy(string $name): array
    {
        if (is_a($name, Event::class, true)) {
            /** @var list<class-string<Event>> $hierarchy */
            $hierarchy = class_parents($name);
            return [$name, ...$hierarchy];
        }

        throw new LogicException("{$name} must be an instance of " . Event::class);
    }

    /**
     * @param array<int, Closure> $callbacks
     * @param mixed ...$args
     * @return void
     */
    protected function invokeCallbacks(array $callbacks, mixed ...$args): void
    {
        if (count($callbacks) > 0) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }
}
