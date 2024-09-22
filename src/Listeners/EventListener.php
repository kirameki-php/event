<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Kirameki\Event\Event;

/**
 * @template TEvent of Event
 */
interface EventListener
{
    /**
     * @return class-string<TEvent>
     */
    public function getEventClass(): string;

    /**
     * @param TEvent $event
     */
    public function invoke(Event $event): void;

    /**
     * @param self<Event> $listener
     * @return bool
     */
    public function isEqual(mixed $listener): bool;
}
