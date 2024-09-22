<?php declare(strict_types=1);

namespace Kirameki\Event\Listeners;

use Closure;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Event\Event;
use Override;
use ReflectionFunction;
use ReflectionNamedType;
use function is_a;

/**
 * @template TEvent of Event
 * @implements EventListener<TEvent>
 */
class CallbackListener implements EventListener
{
    /**
     * @param Closure(TEvent): mixed $callback
     * @param class-string<TEvent>|null $eventClass
     */
    public function __construct(
        protected readonly Closure $callback,
        protected ?string $eventClass = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getEventClass(): string
    {
        return $this->eventClass ??= $this->resolveEventClass();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function invoke(Event $event): void
    {
        ($this->callback)($event);
        if ($this->evictAfterInvocation()) {
            $event->evictCallback();
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isEqual(mixed $listener): bool
    {
        return $listener === $this;
    }

    /**
     * @return bool
     */
    protected function evictAfterInvocation(): bool
    {
        return false;
    }

    /**
     * The callback must have an Event as the first parameter.
     * @return class-string<TEvent>
     */
    protected function resolveEventClass(): string
    {
        $paramReflection = (new ReflectionFunction($this->callback))->getParameters()[0] ?? null;
        $type = $paramReflection?->getType();
        $name = ($type instanceof ReflectionNamedType)
            ? $type->getName()
            : '';

        if (!is_a($name, Event::class, true)) {
            throw new LogicException('The first parameter of the callback must be an instance of Event.', [
                'callback' => $this->callback,
            ]);
        }

        /** @var class-string<TEvent> */
        return $name;
    }
}
