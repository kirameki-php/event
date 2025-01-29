<?php declare(strict_types=1);

namespace Kirameki\Event;

use function array_key_exists;

trait HandlesEvents
{
    /**
     * @var array<class-string<Event>, EventHandler<Event>>
     */
    protected array $eventHandlers = [];

    /**
     * @template TEvent of Event
     * @param TEvent $event
     * @return int
     */
    protected function emitEvent(Event $event): int
    {
        return $this->eventHasListeners($event::class)
            ? $this->resolveEventHandler($event::class)->emit($event)
            : 0;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return bool
     */
    protected function eventHasListeners(string $name): bool
    {
        return (bool) $this->getEventHandlerOrNull($name)?->hasListeners();
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>
     */
    protected function resolveEventHandler(string $name): EventHandler
    {
        /** @var EventHandler<TEvent> */
        return $this->eventHandlers[$name] ??= new EventHandler($name);
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>|null
     */
    protected function getEventHandlerOrNull(string $name): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->eventHandlers[$name] ?? null;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return bool
     */
    protected function removeEventHandler(string $name): bool
    {
        if (array_key_exists($name, $this->eventHandlers)) {
            unset($this->eventHandlers[$name]);
            return true;
        }
        return false;
    }
}
