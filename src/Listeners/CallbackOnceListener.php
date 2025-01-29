<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Kirameki\Event\Event;
use Override;

/**
 * @template TEvent of Event
 * @extends CallbackListener<TEvent>
 */
class CallbackOnceListener extends CallbackListener
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function evictAfterInvocation(): bool
    {
        return true;
    }
}
