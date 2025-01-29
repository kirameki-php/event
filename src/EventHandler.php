<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Kirameki\Event\Listeners\EventListener;
use function array_unshift;
use function count;
use function current;
use function is_a;
use function next;

/**
 * @template TEvent of Event
 */
class EventHandler
{
    /**
     * @var list<EventListener<TEvent>>
     */
    public protected(set) array $listeners = [];

    /**
     * @param class-string<TEvent> $class
     */
    public function __construct(
        public readonly string $class = Event::class,
    )
    {
        if (!is_a($class, Event::class, true)) {
            throw new InvalidArgumentException("Expected class to be instance of " . Event::class . ", got {$class}.");
        }
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter.
     *
     * This method is useful and cleaner than using append() but is slower since
     * it needs to extract the event class name from the callback using reflections.
     *
     * @param Closure(TEvent): mixed $callback
     * @return CallbackListener<TEvent>
     */
    public function do(Closure $callback): CallbackListener
    {
        return $this->append(new CallbackListener($this->class, $callback));
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter. Listener will be
     * removed after it's called once.
     *
     * @param Closure(TEvent): mixed $callback
     * @return CallbackOnceListener<TEvent>
     */
    public function doOnce(Closure $callback): CallbackOnceListener
    {
        return $this->append(new CallbackOnceListener($this->class, $callback));
    }

    /**
     * Append a listener to the end of the list.
     *
     * @template TListener of EventListener<TEvent>
     * @param TListener $listener
     * @return TListener
     */
    public function append(EventListener $listener): EventListener
    {
        $this->listeners[] = $listener;
        return $listener;
    }

    /**
     * Prepend a listener to the end of the list.
     *
     * @template TListener of EventListener<TEvent>
     * @param TListener $listener
     * @return TListener
     */
    public function prepend(EventListener $listener): EventListener
    {
        array_unshift($this->listeners, $listener);
        return $listener;
    }

    /**
     * Returns the number of listeners that were removed.
     *
     * @param EventListener<TEvent> $listener
     * @return int<0, max>
     */
    public function removeListener(EventListener $listener): int
    {
        $indexes = [];
        foreach ($this->listeners as $index => $compare) {
            if ($compare === $listener) {
                $indexes[] = $index;
            }
        }

        $this->evictListeners($indexes);

        return count($indexes);
    }

    /**
     * Returns the number of listeners that were removed.
     *
     * @return int<0, max>
     */
    public function removeAllListeners(): int
    {
        $count = count($this->listeners);
        $this->listeners = [];
        return $count;
    }

    /**
     * @return bool
     */
    public function hasListeners(): bool
    {
        return $this->listeners !== [];
    }

    /**
     * @return bool
     */
    public function hasNoListeners(): bool
    {
        return !$this->hasListeners();
    }

    /**
     * @param TEvent $event
     * Event to be emitted.
     * @param bool $wasCanceled
     * @param-out bool $wasCanceled
     * Flag to be set to true if the event propagation was stopped.
     * @return int<0, max>
     * The number of listeners that were called.
     */
    public function emit(Event $event, bool &$wasCanceled = false): int
    {
        if (!is_a($event, $this->class)) {
            throw new InvalidTypeException("Expected event to be instance of {$this->class}, got " . $event::class);
        }

        $evicting = [];
        $callCount = 0;
        foreach ($this->listeners as $index => $listener) {
            $listener($event);
            $callCount++;
            if ($event->willEvictCallback()) {
                $evicting[] = $index;
            }
            $canceled = $event->isCanceled();
            $event->resetAfterCall();
            if ($canceled) {
                $wasCanceled = true;
                break;
            }
        }

        $this->evictListeners($evicting);

        return $callCount;
    }

    /**
     * @param list<int> $indexes
     * @return void
     */
    protected function evictListeners(array $indexes): void
    {
        if ($indexes === []) {
            return;
        }

        $newListeners = [];
        $removing = current($indexes);
        foreach ($this->listeners as $index => $listener) {
            if ($index !== $removing) {
                $newListeners[] = $listener;
                continue;
            }
            $removing = next($indexes);
        }
        $this->listeners = $newListeners;
    }
}
