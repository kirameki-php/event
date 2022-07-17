<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;

/**
 * @template TEvent of Event
 */
class Listener
{
    /**
     * @var Closure(TEvent, Listener<TEvent>): mixed
     */
    protected Closure $callback;

    /**
     * @var bool
     */
    protected bool $once;

    /**
     * @param Closure(TEvent, Listener<TEvent>): mixed $callback
     * @param bool $once
     */
    public function __construct(Closure $callback, bool $once = false)
    {
        $this->callback = $callback;
        $this->once = $once;
    }

    /**
     * @param TEvent $event
     * @return void
     */
    public function invoke(Event $event): void
    {
        ($this->callback)($event, $this);
    }

    /**
     * @return Closure(TEvent, Listener<TEvent>): mixed
     */
    public function getCallback(): Closure
    {
        return $this->callback;
    }

    /**
     * @return bool
     */
    public function invokedOnlyOnce(): bool
    {
        return $this->once;
    }
}
