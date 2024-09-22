<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Closure;
use Kirameki\Event\Event;
use Override;

/**
 * @template TEvent of Event
 * @extends CallbackListener<TEvent>
 */
class CallbackOnceListener extends CallbackListener
{
    /**
     * @param Closure(TEvent): mixed $callback
     * @param class-string<TEvent>|null $eventClass
     */
    public function __construct(
        Closure $callback,
        ?string $eventClass = null,
    )
    {
        parent::__construct($callback, $eventClass);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function evictAfterInvocation(): bool
    {
        return true;
    }
}
