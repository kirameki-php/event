<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Closure;
use Kirameki\Event\Event;
use Override;

/**
 * @template TEvent of Event
 * @implements EventListener<TEvent>
 */
class CallbackListener implements EventListener
{
    /**
     * @var class-string<TEvent>
     */
    public protected(set) string $eventClass;

    /**
     * @param class-string<TEvent> $eventClass
     * @param Closure(TEvent): mixed $callback
     */
    public function __construct(
        string $eventClass,
        protected readonly Closure $callback,
    )
    {
        $this->eventClass = $eventClass;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(Event $event): void
    {
        ($this->callback)($event);
        if ($this->evictAfterInvocation()) {
            $event->evictCallback();
        }
    }

    /**
     * @return bool
     */
    protected function evictAfterInvocation(): bool
    {
        return false;
    }
}
