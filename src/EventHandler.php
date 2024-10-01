<?php declare(strict_types=1);

namespace Kirameki\Event;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\InvalidTypeException;
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
     * @param class-string<TEvent> $class
     * @param list<EventListener<TEvent>> $listeners
     */
    public function __construct(
        public string $class = Event::class,
        protected array $listeners = [],
    )
    {
        if (!is_a($class, Event::class, true)) {
            throw new InvalidArgumentException("Expected class to be instance of " . Event::class . ", got {$class}");
        }
    }

    /**
     * Append a listener to the end of the list.
     *
     * @param EventListener<TEvent> $listener
     * @return void
     */
    public function append(EventListener $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * Prepend a listener to the end of the list.
     *
     * @param EventListener<TEvent> $listener
     * @return void
     */
    public function prepend(EventListener $listener): void
    {
        array_unshift($this->listeners, $listener);
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
            if ($compare->isEqual($listener)) {
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
     * @param bool|null $wasCanceled
     * Flag to be set to true if the event propagation was stopped.
     * @return int<0, max>
     * The number of listeners that were called.
     */
    public function emit(Event $event, ?bool &$wasCanceled = false): int
    {
        if (!is_a($event, $this->class)) {
            throw new InvalidTypeException("Expected event to be instance of {$this->class}, got " . $event::class);
        }

        $evicting = [];
        $callCount = 0;
        foreach ($this->listeners as $index => $listener) {
            $listener->invoke($event);
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
