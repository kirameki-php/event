<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
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
     * @param Closure(TEvent, Listener<TEvent>): mixed|Listener<TEvent> $callback
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
     * @param Closure(TEvent, Listener<TEvent>): mixed|Listener<TEvent> $callback
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
        foreach ($this->getClassHierarchy($event) as $hierarchy) {
            if (!$this->hasListeners($hierarchy)) {
                continue;
            }

            $listeners = &$this->events[$hierarchy];

            foreach ($listeners as $index => $listener) {
                $listener->invoke($event);

                if ($listener->invokedOnlyOnce()) {
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
     * @template TEvent of Event
     * @param class-string<TEvent> $class
     * @param Closure(): TEvent $callback
     * @return void
     */
    public function dispatchIfListening(string $class, Closure $callback): void
    {
        if ($this->hasListeners($class)) {
            $event = $callback();
            if (!is_a($event, $class)) {
                throw new LogicException('$event must be an instance of '.$class, [
                    'event' => $event,
                    'class' => $class,
                ]);
            }
            $this->dispatch($event);
        }
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent, Listener<TEvent>): mixed|Listener<TEvent> $targetListener
     * @return bool
     */
    public function removeListener(string $name, Closure|Listener $targetListener): bool
    {
        $result = false;

        if (!$this->hasListeners($name)) {
            return $result;
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
                $result = true;
            }
        }

        if (count($listeners) === 0) {
            unset($this->events[$name]);
            $result = true;
        }

        $this->invokeCallbacks($this->removedCallbacks, $name, $targetListener);

        return $result;
    }

    /**
     * @param class-string<Event> $name
     * @return bool
     */
    public function removeAllListeners(string $name): bool
    {
        if (!$this->hasListeners($name)) {
            return false;
        }

        unset($this->events[$name]);
        $this->invokeCallbacks($this->removedCallbacks, $name, null);

        return true;
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
     * @param Event $event
     * @return array<class-string<Event>>
     */
    protected function getClassHierarchy(Event $event): array
    {
        /** @var list<class-string<Event>> $hierarchy */
        $hierarchy = class_parents($event);
        return [$event::class, ...$hierarchy];
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
