<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Kirameki\Event\Event;

/**
 * @template TEvent of Event
 */
interface EventListener
{
    /**
     * @var class-string<TEvent>
     */
    public string $eventClass { get; }

    /**
     * @param TEvent $event
     */
    public function __invoke(Event $event): void;
}
