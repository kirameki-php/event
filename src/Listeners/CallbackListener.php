<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Closure;
use Kirameki\Event\Event;
use Override;

/**
 * @template TEvent of Event
 * @implements EventListenable<TEvent>
 */
class CallbackListener implements EventListenable
{
    /**
     * @param class-string<TEvent> $eventClass
     * @param Closure(TEvent): mixed $callback
     * @param bool $evict
     */
    public function __construct(
        protected readonly string $eventClass,
        protected readonly Closure $callback,
        protected bool $evict = false,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getEventClass(): string
    {
        return $this->eventClass;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function invoke(Event $event): void
    {
        ($this->callback)($event);

        if ($event->willEvictCallback()) {
            $this->evict = true;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function shouldEvict(): bool
    {
        return $this->evict;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isEqual(mixed $listener): bool
    {
        return $listener === $this;
    }
}
