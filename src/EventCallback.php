<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;

/**
 * @template TEvent of Event
 * @internal
 */
final readonly class EventCallback
{
    /**
     * @param Closure(TEvent): mixed $callback
     * @param bool $once
     */
    final public function __construct(
        public Closure $callback,
        public bool $once,
    )
    {
    }

    /**
     * @param TEvent $event
     * @return mixed
     */
    public function __invoke(Event $event): mixed
    {
        return ($this->callback)($event);
    }
}
