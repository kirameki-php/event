<?php declare(strict_types=1);

namespace Tests\Kirameki\Event\Samples;

use Kirameki\Event\Event;
use Kirameki\Event\EventHandler;
use Kirameki\Event\HandlesEvents;

class CustomManager
{
    use HandlesEvents;

    /**
     * @param class-string<Event> $name
     * @return EventHandler<Event>|null
     */
    public function getHandlerOrNull(string $name): ?EventHandler
    {
        return $this->getEventHandlerOrNull($name);
    }

    /**
     * @param class-string<Event> $name
     * @return EventHandler<Event>
     */
    public function resolveHandler(string $name): EventHandler
    {
        return $this->resolveEventHandler($name);
    }

    /**
     * @param class-string<Event> $name
     */
    public function hasListeners(string $name): bool
    {
        return $this->eventHasListeners($name);
    }

    public function emit(Event $event): int
    {
        return $this->emitEvent($event);
    }

    /**
     * @param class-string<Event> $name
     */
    public function removeHandler(string $name): bool
    {
        return $this->removeEventHandler($name);
    }
}
